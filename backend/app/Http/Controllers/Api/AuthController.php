<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $clientId = env('COGNITO_APP_CLIENT_ID');
        $clientSecret = env('COGNITO_APP_CLIENT_SECRET');
        $userPoolId = env('COGNITO_USER_POOL_ID');
        $region = env('COGNITO_REGION', 'us-east-1');

        $secretHash = base64_encode(
            hash_hmac(
                'sha256',
                $request->email . $clientId,
                $clientSecret,
                true
            )
        );

        $client = new CognitoIdentityProviderClient([
            'region' => $region,
            'version' => 'latest'
        ]);

        try {
            $result = $client->adminInitiateAuth([
                'UserPoolId' => $userPoolId,
                'ClientId' => $clientId,
                'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $request->email,
                    'PASSWORD' => $request->password,
                    'SECRET_HASH' => $secretHash,
                ],
            ]);

            // Check if new password is required
            if (isset($result['ChallengeName']) && $result['ChallengeName'] === 'NEW_PASSWORD_REQUIRED') {
                // Set the new password
                $respondResult = $client->respondToAuthChallenge([
                    'ChallengeName' => 'NEW_PASSWORD_REQUIRED',
                    'ClientId' => $clientId,
                    'ChallengeResponses' => [
                        'USERNAME' => $request->email,
                        'NEW_PASSWORD' => $request->password,
                        'SECRET_HASH' => $secretHash,
                    ],
                    'Session' => $result['Session'],
                ]);

                $accessToken = $respondResult['AuthenticationResult']['AccessToken'];

                return response()->json([
                    'success' => true,
                    'token' => $accessToken,
                    'message' => 'Password set and login successful'
                ]);
            }

            // Normal authentication
            $accessToken = $result['AuthenticationResult']['AccessToken'];

            return response()->json([
                'success' => true,
                'token' => $accessToken,
                'message' => 'Login successful'
            ]);

        } catch (\Exception $e) {
            Log::error('Cognito login error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials: ' . $e->getMessage()
            ], 401);
        }
    }
}
