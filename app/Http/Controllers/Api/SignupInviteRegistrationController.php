<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SignupInviteService;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class SignupInviteRegistrationController extends Controller
{
    public function __construct(
        private SignupInviteService $signupInviteService,
        private AuthService $authService
    ) {}

    /**
     * Validate an invite token (public endpoint).
     * This endpoint is called before showing the signup form to the user.
     */
    public function validateToken(string $token): JsonResponse
    {
        $result = $this->signupInviteService->validateToken($token);

        if (!$result['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => $result['message'],
            'invite' => [
                'token' => $result['invite']->token,
                'expires_at' => $result['invite']->expires_at,
                'creator_name' => $result['invite']->creator->name,
            ],
        ]);
    }

    /**
     * Register a new user using an invite token (public endpoint).
     * This is the actual registration endpoint that creates the user account.
     */
    public function register(Request $request, string $token): JsonResponse
    {
        // First validate the invite token
        $validationResult = $this->signupInviteService->validateToken($token);

        if (!$validationResult['valid']) {
            return response()->json([
                'message' => 'Invalid or expired invite token',
                'errors' => ['token' => [$validationResult['message']]],
            ], 400);
        }

        // Validate registration data
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'profile_image_link' => ['nullable', 'string', 'url', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            // Register the user (default role is 'worker' for invite-based signup)
            $user = $this->authService->registerWithInvite($validated, 'worker');

            // Mark the invite as used
            $this->signupInviteService->useInvite($token, $user->id);

            // Create authentication token
            $authToken = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful. Welcome!',
                'user' => new UserResource($user),
                'token' => $authToken,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Registration failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
