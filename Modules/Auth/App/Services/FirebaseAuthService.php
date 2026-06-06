<?php

namespace Modules\Auth\App\Services;

use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\App\Services\OtpService;
use Modules\Auth\App\Models\Profile;

class FirebaseAuthService

{
    public function __construct(
        protected OtpService $otpService
    ) {}

    public function registerManual(array $data): User
    {
        $auth = Firebase::auth();

        $firebaseUser = $auth->createUser([
            'email' => $data['email'],
            'password' => $data['password'],
            'displayName' => $data['name'],
            'emailVerified' => false,
        ]);

        $user = User::create([
            'firebase_uid' => $firebaseUser->uid,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('user');

        $user->profile()->create([
            'full_name' => $data['name'],
            'phone'     => $data['phone'] ?? null,
        ]);

        $otpData = $this->otpService->createRegisterOtp(
            $data['phone'],
            $data['email']
        );

        $this->otpService->sendOtpViaWa($data['phone'], $otpData->otp);

        return $user;
    }
    public function verifyAndSyncUser(string $firebaseUid): User
    {
        $auth = Firebase::auth();

        $firebaseUser = $auth->getUser($firebaseUid);

        $email = $firebaseUser->email;
        $name  = $firebaseUser->displayName ?? 'User';

        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            $user = User::create([
                'name'         => $name,
                'email'        => $email ?? "firebase_{$firebaseUid}@local.test",
                'password'     => Hash::make(str()->random(32)),
                'firebase_uid' => $firebaseUid,
            ]);

            if ($user->roles()->count() === 0) {
                $user->assignRole('user');
            }
        }

        // Auto-create profile if not exists
        if (!$user->profile) {
            $user->profile()->create([
                'full_name' => $user->name,
                'username'  => $this->generateUsername($user->name),
            ]);
        }

        return $user;
    }
    protected function generateUsername(string $name): string
    {
        $base = collect(explode(' ', strtolower($name)))
            ->filter()
            ->take(2)
            ->implode('.');

        $base = Str::slug($base, '.');

        $username = $base;
        $counter = 1;

        while (Profile::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
