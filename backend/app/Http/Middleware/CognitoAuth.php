<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;

class CognitoAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token d\'authentification manquant'
            ], 401);
        }

        try {
            // Récupérer la clé publique JWK depuis Cognito
            $jwksUrl = env('COGNITO_JWK_URL');
            $jwks = json_decode(file_get_contents($jwksUrl), true);

            // Décoder le token sans vérification pour récupérer le kid (key ID)
            $decoded = JWT::decode($token, new Key('', ''));

            // Trouver la clé correspondante dans le JWKS
            $key = null;
            foreach ($jwks['keys'] as $jwk) {
                if ($jwk['kid'] === $decoded->header->kid) {
                    $key = JWT::urlsafeB64Decode($jwk['n']);
                    break;
                }
            }

            if (!$key) {
                throw new \Exception('Clé publique non trouvée');
            }

            // Vérifier le token
            $decoded = JWT::decode($token, new Key($key, 'RS256'));

            // Ajouter les infos utilisateur à la requête
            $request->merge(['user' => (array)$decoded]);

            return $next($request);

        } catch (ExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré'
            ], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Signature de token invalide'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide: ' . $e->getMessage()
            ], 401);
        }
    }
}
