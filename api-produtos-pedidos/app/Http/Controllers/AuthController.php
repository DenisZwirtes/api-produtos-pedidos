<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DTOs\AuthDTO;
use App\Builders\AuthBuilder;
use App\Enums\ValidationMessages;
use App\Enums\HttpStatus;
use App\Exceptions\AuthException;
use InvalidArgumentException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/register",
     *   tags={"Auth"},
     *   summary="Registro de usuário",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"name","email","password","password_confirmation"},
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string"),
     *       @OA\Property(property="password_confirmation", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
	public function register(Request $request)
	{
		$dto = AuthDTO::fromRegisterRequest($request);

		$result = AuthBuilder::create()
			->fromDTO($dto)
			->createWithToken();

		return response()->json($result, HttpStatus::CREATED->value);
	}

    /**
     * @OA\Post(
     *   path="/api/login",
     *   tags={"Auth"},
     *   summary="Login e retorno de token",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function login(Request $request)
	{
		$dto = AuthDTO::fromLoginRequest($request);

		try {
			$result = AuthBuilder::create()
				->fromDTO($dto)
				->loginWithToken();

			return response()->json($result);
		} catch (InvalidArgumentException $e) {
			throw AuthException::invalidCredentials();
		}
	}

    /**
     * @OA\Post(
     *   path="/api/logout",
     *   tags={"Auth"},
     *   summary="Logout do usuário",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();
		return response()->json(['message' => ValidationMessages::LOGOUT_SUCESSO->value]);
	}
}
