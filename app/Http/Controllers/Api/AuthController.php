<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Mail\OtpPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{


    public function redirectSSO(Request $request)
    {
        $accessToken = $request->token;

        if (!$accessToken) {
            return response()->json([
                'error' => true,
                'message' => 'Tidak ada access token dari server SSO'
            ],401);
        }

        $ssoUrl = env('SSO_URL');

        // 2. GET userinfo dari SSO provider
        $userInfoUrl =  "https://api.bispro.digitaltech.my.id/api/v2/auth/me";

        $userInfo = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
        ])->get($userInfoUrl)->json();

        if (!isset($userInfo['data']['user']['email'])) {
            return response()->json([
                'error'   => true,
                'message' => 'Response tidak mengandung email user',
            ], 400);
        }

        if($userInfo['data']['user']['email_verified_at'] == null){
            return response()->json([
                'error'   => true,
                'message' => 'Akun SSO belum terverifikasi',
            ], 403);

        }

        $mappingRoles = [
            'admin_kota' => 'admin-kota',
            'admin_dinas' => 'admin-opd',
            'kepala_seksi' => 'admin-seksi',
            'kepala_bidang' => 'admin-bidang',
            'teknisi' => 'teknisi',
            'staff' => 'user',
        ];

            
        // Validasi role dari SSO
        $ssoRole = $userInfo['data']['user']['role'] ?? null;

        if (!$ssoRole || !array_key_exists($ssoRole, $mappingRoles)) {
            return response()->json([
                'error'   => true,
                'message' => 'Role SSO tidak dikenali atau tidak memiliki akses',
            ], 403);
        }

        $localRole = $mappingRoles[$ssoRole];

        // Create user jika belum ada
        $user = User::firstOrCreate(
            ['email' => $userInfo['data']['user']['email']],
            [
                'sso_id' => $userInfo['data']['user']['id'],
                'name'   => $userInfo['data']['user']['name'],
                'password' => Hash::make("password"),
                'email_verified_at' => now(),
                'role' => $localRole,
            ]
        );

        $user->syncRoles([$localRole]);

        $token = $user->createToken('auth_token')->plainTextToken;


        $redirectUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/sso/callback?token=' . $token;

        return redirect($redirectUrl);
    }




    public function register(Request $request)
    {
        try {
            $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email',
                'password'              => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        }
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            if (!is_null($existingUser->email_verified_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email sudah terdaftar dan sudah terverifikasi.',
                ], 409);
            }
            $user = $existingUser;
            $user->name = $request->name;
            $user->password = Hash::make($request->password);
        } else {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);
        }
        $plainOtp = rand(100000, 999999);
        $expiresInMinutes = 10;

        $user->otp = Hash::make($plainOtp);
        $user->otp_expires_at = Carbon::now()->addMinutes($expiresInMinutes);
        $user->save();

        Mail::to($user->email)->send(new OtpMail($plainOtp, $expiresInMinutes, $user));

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil. Kode OTP telah dikirim ke email.',
            'data'   => $user,
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if (!$user->otp || !$user->otp_expires_at) {
            return response()->json(['success' => false, 'message' => 'Tidak ada OTP aktif. Silakan minta ulang.'], 400);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP sudah kadaluarsa. Silakan minta ulang.'], 400);
        }

        if (!Hash::check($request->otp, $user->otp)) {
            return response()->json(['success' => false, 'message' => 'OTP salah.'], 400);
        }

        $user->email_verified_at = now();
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil.',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if (!is_null($user->email_verified_at)) {
            return response()->json(['success' => false, 'message' => 'Akun sudah terverifikasi.'], 400);
        }
        $cacheKey = 'resend_otp_cooldown_' . $user->id;
        $cooldownSeconds = 60;

        if (Cache::has($cacheKey)) {
            $secondsLeft = Cache::ttl($cacheKey);
            return response()->json([
                'success' => false,
                'message' => "Tunggu {$secondsLeft} detik sebelum mengirim ulang OTP."
            ], 429);
        }

        $plainOtp = rand(100000, 999999);
        $expiresInMinutes = 10;

        $user->otp = Hash::make($plainOtp);
        $user->otp_expires_at = Carbon::now()->addMinutes($expiresInMinutes);
        $user->save();

        try {
            Mail::to($user->email)->send(new OtpMail($plainOtp, $expiresInMinutes, $user));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $e->getMessage()
            ], 500);
        }

        Cache::put($cacheKey, true, $cooldownSeconds);

        return response()->json([
            'success' => true,
            'message' => 'OTP baru telah dikirim ke email.',
        ]);
    }

    /**
 * @OA\Post(
 *     path="/api/login",
 *     tags={"Auth"},
 *     summary="Login user",
 *     description="Login menggunakan email dan password. Token akan dikembalikan jika login berhasil dan akun sudah terverifikasi.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login berhasil",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
 *                 @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-11-10T08:00:00Z")
 *             ),
 *             @OA\Property(property="token", type="string", example="1|abc123def456...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Email atau password salah",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Email atau password salah"),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array",
 *                     @OA\Items(type="string", example="Email atau password salah")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Akun belum terverifikasi",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Akun belum terverifikasi. Silakan cek email untuk kode OTP atau minta kirim ulang.")
 *         )
 *     )
 * )
 */
    public function login(Request $request)
    {

        // return redirect('https://bispro.digitaltech.my.id/');
            

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'errors'  => [
                    'email' => ['Email atau password salah'],
                ]
            ], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum terverifikasi. Silakan cek email untuk kode OTP atau minta kirim ulang.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function me(Request $request)
    {
        // $user = $request->user()->load('main_address');
        $user = $request->user();
        return response()->json($user);
    }

    
    /**
 * @OA\Post(
 *     path="/api/resend-otp/forgot-password",
 *     tags={"Auth"},
 *     summary="Kirim OTP untuk reset password",
 *     description="Mengirim OTP ke email pengguna untuk proses reset password.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email"},
 *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP berhasil dikirim",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="OTP untuk reset password telah dikirim ke email.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User tidak ditemukan",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="User tidak ditemukan.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validasi gagal",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Validasi gagal."),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array",
 *                     @OA\Items(type="string", example="The email field is required.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=429,
 *         description="Cooldown OTP",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Tunggu 45 detik sebelum mengirim ulang OTP.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Gagal mengirim OTP",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Gagal mengirim OTP: SMTP Error ...")
 *         )
 *     )
 * )
 */
    public function sendForgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }
        $cacheKey = 'forgot_pw_cooldown_' . $user->id;
        $cooldownSeconds = 60;

        if (Cache::has($cacheKey)) {
            $secondsLeft = Cache::ttl($cacheKey);
            return response()->json([
                'success' => false,
                'message' => "Tunggu {$secondsLeft} detik sebelum mengirim ulang OTP."
            ], 429);
        }

        $plainOtp = rand(100000, 999999);
        $expiresInMinutes = 10;

        $user->otp = Hash::make($plainOtp);
        $user->otp_expires_at = Carbon::now()->addMinutes($expiresInMinutes);
        $user->save();

        try {
            Mail::to($user->email)->send(new OtpPassword($plainOtp, $expiresInMinutes, $user));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $e->getMessage()
            ], 500);
        }

        Cache::put($cacheKey, true, $cooldownSeconds);

        return response()->json([
            'success' => true,
            'message' => 'OTP untuk reset password telah dikirim ke email.',
        ]);
    }

    /**
 * @OA\Post(
 *     path="/api/check-otp/forgot-password",
 *     tags={"Auth"},
 *     summary="Cek OTP pengguna",
 *     description="Memvalidasi OTP yang dikirim ke email sebelum melakukan reset password.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","otp"},
 *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
 *             @OA\Property(property="otp", type="string", example="123456")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP valid",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="OTP valid. Silakan lanjut ke halaman ganti password sebelum 2025-11-10T18:00:00")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="OTP kadaluarsa atau salah / tidak ada OTP aktif",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="OTP salah.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User tidak ditemukan",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="User tidak ditemukan.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validasi gagal",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Validasi gagal."),
 *             @OA\Property(property="errors", type="object",
 *                 @OA\Property(property="email", type="array",
 *                     @OA\Items(type="string", example="The email field is required.")
 *                 ),
 *                 @OA\Property(property="otp", type="array",
 *                     @OA\Items(type="string", example="The otp field is required.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
    public function checkOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }

        if (!$user->otp || !$user->otp_expires_at) {
            return response()->json(['success' => false, 'message' => 'Tidak ada OTP aktif. Silakan minta ulang.'], 400);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP sudah kadaluarsa. Silakan minta ulang.'], 400);
        }

        if (!Hash::check($request->otp, $user->otp)) {
            return response()->json(['success' => false, 'message' => 'OTP salah.'], 400);
        }
        return response()->json([
            'success' => true,
            'message' => 'OTP valid. Silakan lanjut ke halaman ganti password sebelum ' . $user->otp_expires_at,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
        }
        if (!$user->otp || !$user->otp_expires_at) {
            return response()->json(['success' => false, 'message' => 'Tidak ada OTP aktif. Silakan minta ulang.'], 400);
        }
        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP sudah kadaluarsa. Silakan minta ulang.'], 400);
        }
        if (!Hash::check($request->otp, $user->otp)) {
            return response()->json(['success' => false, 'message' => 'OTP salah.'], 400);
        }
        $user->password = Hash::make($request->password);
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login menggunakan password baru.',
        ]);
    }
}