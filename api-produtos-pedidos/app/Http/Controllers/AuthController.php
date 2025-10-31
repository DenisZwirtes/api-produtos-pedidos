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
	public function register(Request $request)
	{
		$dto = AuthDTO::fromRegisterRequest($request);

		$result = AuthBuilder::create()
			->fromDTO($dto)
			->createWithToken();

		return response()->json($result, HttpStatus::CREATED->value);
	}

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

	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();
		return response()->json(['message' => ValidationMessages::LOGOUT_SUCESSO->value]);
	}
}
