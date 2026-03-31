<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully',
            'data' => User::all()
        ]);
    }

    public function live()
    {

        echo json_encode([
            'isOk' => true,
            'status' => 200,
            'message' => 'Users Controller is live!'
        ]);
    }

    public function addUser(Request $request)
    {
        try
        {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'userTypeId' => 'required|integer'
            ]);

            $userName = isset($request->userName) ? trim($request->userName) : '';
            $password = isset($request->password) ? trim($request->password) : '';
            $credentialsResult = $this->resolveUserCredentials($userName, $password);
            if (!$credentialsResult['isOk'])
            {
                return $this->sendResponse($credentialsResult['message'], $credentialsResult['status'], []);
            }

            $userName = $credentialsResult['username'];
            $password = $credentialsResult['password'];
            $isGeneratedCredentials = $credentialsResult['isGeneratedCredentials'];

            $userId = DB::table('users')->insertGetId([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'user_name' => $userName,
                'password' => base64_encode($password),
                'user_type_id' => $validatedData['userTypeId'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $responseData = [
                'user_id' => $userId,
                'username' => $userName,
                'password' => $password,
                'is_generated_credentials' => $isGeneratedCredentials
            ];
            return $this->sendResponse('User added successfully', 200, $responseData);
        }
        catch (\Illuminate\Validation\ValidationException $validationException)
        {
            return $this->sendResponse('Validation failed', 422, $validationException->errors());
        }
        catch (\Throwable $exception)
        {
            Log::error('addUser failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    private function resolveUserCredentials($userName, $password)
    {
        $isGeneratedCredentials = false;

        if ($userName === '' || $password === '')
        {
            $isGeneratedCredentials = true;
            $maxAttempt = 20;
            $attempt = 0;
            do
            {
                $attempt++;
                $credentials = $this->generateCredentials(10);
                $userName = $credentials['username'];
                $password = $credentials['password'];
            }
            while ($this->isUsernameExists($userName) && $attempt < $maxAttempt);

            if ($this->isUsernameExists($userName))
            {
                return [
                    'isOk' => false,
                    'status' => 500,
                    'message' => 'Unable to generate unique credentials',
                    'username' => '',
                    'password' => '',
                    'isGeneratedCredentials' => true
                ];
            }
        }
        else if ($this->isUsernameExists($userName))
        {
            return [
                'isOk' => false,
                'status' => 409,
                'message' => 'Username already exists',
                'username' => '',
                'password' => '',
                'isGeneratedCredentials' => false
            ];
        }

        return [
            'isOk' => true,
            'status' => 200,
            'message' => '',
            'username' => $userName,
            'password' => $password,
            'isGeneratedCredentials' => $isGeneratedCredentials
        ];
    }

    private function isUsernameExists($userName)
    {
        $checkQuery = "
            SELECT
                id
            FROM
                users
            WHERE
                user_name = :userName
            LIMIT 1
        ";
        $existingUser = DB::select($checkQuery, ['userName' => $userName]);
        return !empty($existingUser);
    }

    public function login(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $userName = $requestData['username'];
            $password = $requestData['password'];

            $query = "
                SELECT
                    id,
                    name,
                    email,
                    user_name,
                    password,
                    user_type_id
                FROM
                    users
                WHERE
                    user_name = :userName
                LIMIT 1
            ";
            $user = DB::select($query, ['userName' => $userName]);
            if (empty($user))
            {
                return $this->sendResponse('Invalid username or password', 401, []);
            }

            $userData = $user[0];
            $storedPassword = $userData->password;

            $isPasswordValid = false;
            if (base64_encode($password) === $storedPassword)
            {
                $isPasswordValid = true;
            }
            else
            {
                try
                {
                    if (Hash::check($password, $storedPassword))
                    {
                        $isPasswordValid = true;
                    }
                }
                catch (\Throwable $exception)
                {
                    $isPasswordValid = false;
                }
            }

            if (!$isPasswordValid)
            {
                return $this->sendResponse('Invalid username or password', 401, []);
            }

            $responseData = [
                'user_id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email,
                'user_name' => $userData->user_name,
                'user_type_id' => $userData->user_type_id
            ];
            return $this->sendResponse('Login successful', 200, $responseData);
        }
        catch (\Throwable $exception)
        {
            Log::error('login failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function sendOtp(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $email = $requestData['email'];

            // Raw SELECT query
            $query = "
                SELECT 
                    id 
                FROM 
                    users 
                WHERE email = :email
                AND status = 'active'
            ";
            $user = DB::select($query, ['email' => $email]);
            
            if (empty($user))
            {
                return $this->sendResponse('Email not found', 404, []);
            }
            $userId = $user[0]->id;
            
            $otp = rand(100000, 999999);

            $checkOtpQuery = "
                SELECT 
                    user_id 
                FROM 
                    add_otp_to_users 
                WHERE 
                    user_id = :userId
                    AND otp_expire_at > NOW()
            ";
            $checkOtp = DB::select($checkOtpQuery, ['userId' => $userId]);
            if (!empty($checkOtp))
            {
                return $this->sendResponse('OTP already sent. Please wait for 5 minutes to send a new OTP.', 400, []);
            }

            $insertQuery = "
                INSERT INTO 
                    add_otp_to_users 
                    (user_id, otp, otp_expire_at, created_at, updated_at) 
                VALUES 
                (:userId, :otp, DATE_ADD(NOW(), INTERVAL 5 MINUTE), now(), now())
            ";
            DB::insert($insertQuery, 
            [
                'userId' => $userId, 
                'otp' => $otp
            ]);

            // Send email (same as before)
            $subject = 'OneHourNTPC - Password Reset OTP';
            $messageBody = "
                <p>Hello,</p> <br>
                <p>We received a request to reset your password for your <strong>OneHourNTPC</strong> account.</p>
                <p><strong>Your OTP (One-Time Password) is:</strong></p>
                <h2 style='letter-spacing: 2px;'>$otp</h2>
                <p>This OTP is valid for <strong>5 minutes</strong>.</p>
                <p>If you did not request a password reset, please ignore this email or contact our support team immediately.</p> <br>
                <p>For security reasons, <strong>do not share this OTP with anyone.</strong></p><br>

                <p>Regards,<br>
                Team OneHourNTPC</p>
            ";

            $this->sendEmail($email, $subject, $messageBody);

            return $this->sendResponse('OTP sent successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('sendOtp failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function verifyOtp(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $email = $requestData['email'];
            $otp = $requestData['otp'];

            $checkOtpQuery = "
                SELECT 
                    user_id 
                FROM 
                    add_otp_to_users as aotu
                INNER JOIN 
                    users as u
                    ON aotu.user_id = u.id
                WHERE 
                    u.email = :email
                    AND aotu.otp = :otp
                    AND aotu.otp_expire_at > NOW()
            ";
            $checkOtp = DB::select($checkOtpQuery, ['email' => $email, 'otp' => $otp]);
            if (empty($checkOtp))
            {
                return $this->sendResponse('Invalid OTP', 400, []);
            }

            return $this->sendResponse('OTP verified successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('verifyOtp failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function resetPassword(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $email = $requestData['email'];
            $password = $requestData['password'];

            $query = "
                SELECT
                    id
                FROM
                    users
                WHERE email = :email
                LIMIT 1
            ";
            $user = DB::select($query, ['email' => $email]);
            if (empty($user))
            {
                return $this->sendResponse('Email not found', 404, []);
            }

            $userId = $user[0]->id;

            $updateQuery = "
                UPDATE
                    users
                SET
                    password = :password,
                    updated_at = NOW()
                WHERE id = :userId
            ";
            DB::update($updateQuery, [
                'password' => base64_encode($password),
                'userId' => $userId
            ]);

            $clearOtpQuery = "
                DELETE FROM
                    add_otp_to_users
                WHERE user_id = :userId
            ";
            DB::delete($clearOtpQuery, ['userId' => $userId]);

            return $this->sendResponse('Password reset successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('resetPassword failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function getTeachers()
    {
        try
        {
            $query = "
                SELECT
                    id,
                    name,
                    email,
                    user_name,
                    password
                FROM
                    users
                WHERE
                    user_type_id = 3
                    AND status = 'active'
                ORDER BY
                    id DESC
            ";
            $teachers = DB::select($query);
            return $this->sendResponse('Teachers fetched successfully', 200, $teachers);
        }
        catch (\Throwable $exception)
        {
            Log::error('getTeachers failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function updateTeacher(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $teacherId = $requestData['id'];
            $name = $requestData['name'];
            $email = $requestData['email'];
            $userName = $requestData['userName'];
            $password = isset($requestData['password']) ? trim($requestData['password']) : '';

            if ($password !== '')
            {
                $updateQuery = "
                    UPDATE
                        users
                    SET
                        name = :name,
                        email = :email,
                        user_name = :userName,
                        password = :password,
                        updated_at = NOW()
                    WHERE
                        id = :teacherId
                        AND user_type_id = 3
                ";
                DB::update($updateQuery, [
                    'name' => $name,
                    'email' => $email,
                    'userName' => $userName,
                    'password' => Hash::make($password),
                    'teacherId' => $teacherId
                ]);
            }
            else
            {
                $updateQuery = "
                    UPDATE
                        users
                    SET
                        name = :name,
                        email = :email,
                        user_name = :userName,
                        updated_at = NOW()
                    WHERE
                        id = :teacherId
                        AND user_type_id = 3
                ";
                DB::update($updateQuery, [
                    'name' => $name,
                    'email' => $email,
                    'userName' => $userName,
                    'teacherId' => $teacherId
                ]);
            }

            return $this->sendResponse('Teacher updated successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('updateTeacher failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function deleteTeacher(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $teacherId = $requestData['id'];

            $updateQuery = "
                UPDATE
                    users
                SET
                    status = 'deleted',
                    updated_at = NOW()
                WHERE
                    id = :teacherId
                    AND user_type_id = 3;
            ";
            DB::update($updateQuery, [
                'teacherId' => $teacherId
            ]);

            return $this->sendResponse('Teacher deleted successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('deleteTeacher failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }
}