<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
                'email' => 'required|email|max:255'
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

            $getLastUserIdQuery = "
                SELECT
                    id
                FROM
                    users
                WHERE
                    status = 'active'
                    AND user_type_id = 2
                ORDER BY
                    id DESC
                LIMIT 1
            ";
            $lastUserRows = DB::select($getLastUserIdQuery);
            $newUserId = empty($lastUserRows) ? 1 : ($lastUserRows[0]->id + 1);

            $userId = DB::table('users')->insertGetId([
                'institute_id' => $newUserId,
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'user_name' => $userName,
                'password' => base64_encode($password),
                'user_type_id' => 2,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $responseData = [
                'user_id' => $userId,
                'institute_id' => $newUserId,
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

    /**
     * Create an academic year for an institute and link it (institutes.academic_year_id or users.academic_year_id).
     * Table academic_years uses column name insitute_id (as in the live schema).
     */
    public function addAcademicYear(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'institute_id' => 'required|integer|min:1',
                'name' => 'required|string|max:255',
                'session_start' => 'required|date',
                'session_end' => 'required|date|after_or_equal:session_start',
                'status' => 'sometimes|string|in:active,inactive,deleted,legacy',
                'school_user_id' => 'nullable|integer|min:1',
            ]);

            $status = isset($validated['status']) ? $validated['status'] : 'active';
            $instituteId = (int) $validated['institute_id'];
            $schoolUserId = isset($validated['school_user_id']) ? (int) $validated['school_user_id'] : null;
            $linkToInstitute = $request->boolean('link_to_institute', true);

            $academicYearId = DB::table('academic_years')->insertGetId([
                'insitute_id' => $instituteId,
                'name' => $validated['name'],
                'session_start' => $validated['session_start'],
                'session_end' => $validated['session_end'],
                'status' => $status,
            ]);

            if ($linkToInstitute)
            {
                $this->linkAcademicYearToSchool($academicYearId, $instituteId, $schoolUserId);
            }

            return $this->sendResponse('Academic year created successfully', 200, [
                'academic_year_id' => $academicYearId,
                'institute_id' => $instituteId,
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $validationException)
        {
            return $this->sendResponse('Validation failed', 422, $validationException->errors());
        }
        catch (\Throwable $exception)
        {
            Log::error('addAcademicYear failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    /**
     * Soft-delete an academic year (status = deleted).
     */
    public function deleteAcademicYear(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
                'institute_id' => 'required|integer|min:1',
            ]);

            $id = (int) $validated['id'];
            $instituteId = (int) $validated['institute_id'];

            $updated = DB::table('academic_years')
                ->where('id', $id)
                ->where('insitute_id', $instituteId)
                ->update(['status' => 'deleted']);

            if ($updated === 0)
            {
                return $this->sendResponse('Academic year not found or access denied', 404, []);
            }

            return $this->sendResponse('Academic year deleted successfully', 200, [
                'id' => $id,
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $validationException)
        {
            return $this->sendResponse('Validation failed', 422, $validationException->errors());
        }
        catch (\Throwable $exception)
        {
            Log::error('deleteAcademicYear failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    /**
     * List academic years for an institution (excludes soft-deleted rows).
     */
    public function listAcademicYears(Request $request)
    {
        try
        {
            $validated = $request->validate([
                'institute_id' => 'required|integer|min:1',
            ]);

            $instituteId = (int) $validated['institute_id'];

            $rows = DB::table('academic_years')
                ->where('insitute_id', $instituteId)
                ->where('status', '!=', 'deleted')
                ->orderByDesc('id')
                ->get()
                ->map(function ($row)
                {
                    return (array) $row;
                })
                ->values()
                ->all();

            $linkedAcademicYearId = null;
            if (Schema::hasTable('institutes'))
            {
                $linkedAcademicYearId = DB::table('institutes')
                    ->where('id', $instituteId)
                    ->value('academic_year_id');
            }
            elseif (Schema::hasColumn('users', 'academic_year_id'))
            {
                $linkedAcademicYearId = DB::table('users')
                    ->where('institute_id', $instituteId)
                    ->where('user_type_id', 2)
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->value('academic_year_id');
            }

            return $this->sendResponse('Academic years fetched successfully', 200, [
                'rows' => $rows,
                'linked_academic_year_id' => $linkedAcademicYearId,
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $validationException)
        {
            return $this->sendResponse('Validation failed', 422, $validationException->errors());
        }
        catch (\Throwable $exception)
        {
            Log::error('listAcademicYears failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    /**
     * Sets academic_year_id on the school row: prefers institutes.id, else users.id when column exists.
     */
    protected function linkAcademicYearToSchool(int $academicYearId, int $instituteId, ?int $schoolUserId): void
    {
        if (Schema::hasTable('institutes'))
        {
            DB::table('institutes')->where('id', $instituteId)->update([
                'academic_year_id' => $academicYearId,
            ]);

            return;
        }

        if ($schoolUserId !== null && Schema::hasColumn('users', 'academic_year_id'))
        {
            DB::table('users')->where('id', $schoolUserId)->update([
                'academic_year_id' => $academicYearId,
            ]);
        }
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
                    user_type_id,
                    institute_id
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
                'user_type_id' => $userData->user_type_id,
                'institute_id' => $userData->institute_id,
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

}