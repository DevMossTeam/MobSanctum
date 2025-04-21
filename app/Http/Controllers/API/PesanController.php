<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Pesan;
use Carbon\Carbon;
// Tambahkan import TextPart
use Symfony\Component\Mime\Part\TextPart;

class PesanController extends Controller
{
    /**
     * Simpan pesan dari user dan kirim email HTML bergaya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $validator = Validator::make($request->all(), [
            'pesan' => 'required|string',
            'email' => 'required|email',
            'nama'  => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2. Generate ID acak unik
        do {
            $id = Str::upper(Str::random(10)); // Ex: 7XG9YKD2WQ
        } while (Pesan::where('id', $id)->exists());

        // 3. Simpan pesan
        $pesan = Pesan::create([
            'id'           => $id,
            'pesan'        => $request->pesan,
            'email'        => $request->email,
            'nama'         => $request->nama,
            'status'       => 'masukan',
            'status_read'  => 'belum',
            'created_at'   => Carbon::now(),
        ]);

        // 4. Siapkan konten email
        $plaintext = <<<TEXT
        Halo {$pesan->nama},

        Terima kasih telah mengirimkan masukan kepada kami.
        ID Pesan Anda: {$pesan->id}

        Kami akan menanggapi masukan Anda secepat mungkin.

        Salam hangat,
        Tim Dukungan MediaExplant
        TEXT;

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="id" xmlns="http://www.w3.org/1999/xhtml">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
          <title>Konfirmasi Masukan</title>
          <style type="text/css">
            body, table, td, p, a, li, blockquote {
              -webkit-text-size-adjust:100%;
              -ms-text-size-adjust:100%;
            }
            table, td {
              mso-table-lspace:0pt;
              mso-table-rspace:0pt;
            }
            img {
              -ms-interpolation-mode:bicubic;
            }
            body {
              margin:0;
              padding:0;
              width:100% !important;
              height:100% !important;
              font-family:Arial,sans-serif;
              background-color:#f4f4f4;
            }
            @media only screen and (max-width:620px) {
              .wrapper  { width:100% !important; padding:0 !important; }
              .content  { padding:20px !important; }
              .outer    { width:100% !important; border-radius:0 !important; }
            }
            a { color:inherit; text-decoration:underline; }
            .ExternalClass { width:100%; line-height:100%; }
          </style>
        </head>
        <body>
          <center class="wrapper" style="width:100%;table-layout:fixed;background-color:#f4f4f4;padding:20px 0;">
            <table class="outer" align="center" cellpadding="0" cellspacing="0" width="600"
                   style="margin:0 auto;background:#FCFCFC;border-radius:16px;overflow:hidden;
                          box-shadow:0 4px 16px rgba(0,0,0,0.08);">
              <!-- Body -->
              <tr>
                <td class="content" style="padding:32px;color:#333333;line-height:1.7;font-size:16px;">
                  <p style="margin:0 0 16px;">
                    Hai <strong>{$pesan->nama}</strong>,
                  </p>
                  <p style="margin:0 0 16px;">
                    Terima kasih telah mengirimkan <strong>masukan</strong> kepada kami.
                  </p>
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                         style="margin:24px auto;">
                    <tr>
                      <td style="background:#f0f0f0;padding:14px 24px;border-radius:12px;
                                 font-family:monospace;font-size:16px;color:#333333;">
                        ID Pesan Anda: <strong>{$pesan->id}</strong>
                      </td>
                    </tr>
                  </table>
                  <p style="margin:24px 0 0;">
                    Kami akan menanggapi masukan Anda secepat mungkin.
                  </p>
                </td>
              </tr>
              <!-- Footer -->
              <tr>
                <td style="background:#fafafa;padding:16px;text-align:center;color:#777777;font-size:12px;">
                  <p style="margin:0;">
                    &copy; 2025 MediaExplant<br>
                    <a href="https://yourdomain.com/privacy" style="color:#777777;text-decoration:underline;">
                      Privacy Policy
                    </a>
                    &nbsp;|&nbsp;
                    <a href="https://yourdomain.com/terms" style="color:#777777;text-decoration:underline;">
                      Terms of Service
                    </a>
                  </p>
                </td>
              </tr>
            </table>
          </center>
        </body>
        </html>
        HTML;



        // 5. Kirim email multipart dengan TextPart
        try {
            Mail::send([], [], function ($message) use ($pesan, $plaintext, $html) {
                $message->to($pesan->email, $pesan->nama)
                        ->subject('Konfirmasi Masukan Anda');

                // Dapatkan Symfony Email instance
                $symfony = $message->getSymfonyMessage();

                // Set multipart
                $symfony->text($plaintext, 'utf-8');
                $symfony->html($html, 'utf-8');
            });

            $emailStatus = 'Email konfirmasi HTML berhasil dikirim.';
        } catch (\Exception $e) {
            Log::error('Gagal kirim email konfirmasi: '.$e->getMessage());
            $emailStatus = 'Pesan terkirim, namun email konfirmasi gagal.';
        }

        // 6. Respon API
        return response()->json([
            'success'      => true,
            'message'      => 'Pesan berhasil dikirim!',
            'email_status' => $emailStatus,
            'data'         => [
                'id'     => $pesan->id,
                'nama'   => $pesan->nama,
                'email'  => $pesan->email,
                'status' => $pesan->status,
                'waktu'  => $pesan->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
