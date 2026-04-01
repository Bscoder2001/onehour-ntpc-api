<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class V2UsersController extends Controller
{
    public function addMember(Request $request)
    {
        Log::info('addMember request received');
        try
        {
            $requestData = $request->all();
            $name = $requestData['name'];
            $instituteId = $requestData['instituteId'];
            $email = $requestData['email'];
            $userName = isset($request->userName) ? trim($request->userName) : '';
            $password = isset($request->password) ? trim($request->password) : '';
            $userTypeId = $requestData['userTypeId'];

            if (empty($name) || empty($instituteId) || empty($email))
            {
                return $this->sendResponse('Invalid params passed!', 400);
            }

            $credentialsResult = $this->resolveUserCredentials($userName, $password);
            if (!$credentialsResult['isOk'])
            {
                return $this->sendResponse($credentialsResult['message'], $credentialsResult['status'], []);
            }

            $userName = $credentialsResult['username'];
            $password = $credentialsResult['password'];
            $isGeneratedCredentials = $credentialsResult['isGeneratedCredentials'];

            $userId = DB::table('users')->insertGetId([
                'institute_id' => $instituteId,
                'name' => $name,
                'email' => $email,
                'user_name' => $userName,
                'password' => base64_encode($password),
                'user_type_id' => $userTypeId,
                'status' => 'active',
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

    public function getMembers(Request $request)
    {
        try
        {
            $requestData = $request->all();
            if (!isset($requestData['userTypeId']) || $requestData['userTypeId'] === ''
                || !isset($requestData['instituteId']) || $requestData['instituteId'] === '')
            {
                return $this->sendResponse('instituteId and userTypeId are required', 400, []);
            }
            $userTypeId = $requestData['userTypeId'];
            $instituteId = $requestData['instituteId'];

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
                    user_type_id = :userTypeId
                    AND institute_id = :instituteId
                    AND status = 'active'
                ORDER BY
                    id DESC
            ";
            $members = DB::select($query, ['userTypeId' => $userTypeId, 'instituteId' => $instituteId]);
            return $this->sendResponse('Members fetched successfully', 200, $members);
        }
        catch (\Throwable $exception)
        {
            Log::error('getMembers failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function updateMember(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $memberId = $requestData['id'];
            $name = $requestData['name'];
            $email = $requestData['email'];
            $userName = $requestData['userName'];
            $password = isset($requestData['password']) ? trim($requestData['password']) : '';
            $userTypeId = $requestData['userTypeId'];
            $instituteId = $requestData['instituteId'];

            if ($instituteId === null || $instituteId === '')
            {
                return $this->sendResponse('instituteId is required', 400, []);
            }

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
                        id = :memberId
                        AND user_type_id = :userTypeId
                        AND institute_id = :instituteId
                ";
                DB::update($updateQuery, [
                    'name' => $name,
                    'email' => $email,
                    'userName' => $userName,
                    'password' => base64_encode($password),
                    'memberId' => $memberId,
                    'userTypeId' => $userTypeId,
                    'instituteId' => $instituteId,
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
                        id = :memberId
                        AND user_type_id = :userTypeId
                        AND institute_id = :instituteId
                ";
                DB::update($updateQuery, [
                    'name' => $name,
                    'email' => $email,
                    'userName' => $userName,
                    'memberId' => $memberId,
                    'userTypeId' => $userTypeId,
                    'instituteId' => $instituteId,
                ]);
            }

            return $this->sendResponse('Member updated successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('updateMember failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

    public function deleteMember(Request $request)
    {
        try
        {
            $requestData = $request->all();
            $memberId = $requestData['id'];
            $userTypeId = $requestData['userTypeId'];
            $instituteId = $requestData['instituteId'] ?? null;

            if ($instituteId === null || $instituteId === '')
            {
                return $this->sendResponse('instituteId is required', 400, []);
            }

            $updateQuery = "
                UPDATE
                    users
                SET
                    status = 'deleted',
                    updated_at = NOW()
                WHERE
                    id = :memberId
                    AND user_type_id = :userTypeId
                    AND institute_id = :instituteId
            ";
            DB::update($updateQuery, [
                'memberId' => $memberId,
                'userTypeId' => $userTypeId,
                'instituteId' => $instituteId,
            ]);

            return $this->sendResponse('Member deleted successfully', 200, []);
        }
        catch (\Throwable $exception)
        {
            Log::error('deleteMember failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return $this->sendResponse($exception->getMessage(), 500, []);
        }
    }

}
