<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PendaftaranOsisResource;
use App\Models\PendaftaranOsis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PendaftaranOsisController extends Controller
{
    /**
     * Menampilkan daftar seluruh pendaftaran OSIS.
     */
    public function index(): AnonymousResourceCollection
    {
        // L1: Eager loading relasi siswa beserta kelas dan jenis_kelamin-nya
        $pendaftaran = PendaftaranOsis::with(['siswa.kelas', 'siswa.jenisKelamin'])->get();

        return PendaftaranOsisResource::collection($pendaftaran);
    }

    /**
     * Menampilkan detail satu pendaftaran OSIS berdasarkan ID.
     */
    public function show(string|int $id): JsonResponse|PendaftaranOsisResource
    {
        $pendaftaran = PendaftaranOsis::with(['siswa.kelas', 'siswa.jenisKelamin'])->find($id);

        if (! $pendaftaran) {
            return response()->json([
                'message' => 'Pendaftaran tidak ditemukan.',
            ], 404);
        }

        return new PendaftaranOsisResource($pendaftaran);
    }

    /**
     * Menyimpan data pendaftaran OSIS baru.
     */
    public function store(Request $request): JsonResponse 
    {
        $validated = $request->validate([
            // L2: Memastikan siswa_id ada di tabel siswa
            // L3: Memastikan satu siswa hanya bisa mendaftar satu kali (unique)
            'siswa_id' => [
                'required', 
                'integer', 
                'exists:siswa,id', 
                'unique:pendaftaran_osis,siswa_id'
            ],
            
            // L4: Membatasi pilihan bidang hanya pada opsi yang ditentukan
            'pilihan_bidang' => [
                'required', 
                'string', 
                'in:kepemimpinan,keagamaan,olahraga,seni'
            ],
            
            // L5: Membatasi panjang karakter minimal 20 dan maksimal 300
            'motivasi' => [
                'required', 
                'string', 
                'min:20', 
                'max:300'
            ],
        ]);

        $pendaftaran = PendaftaranOsis::create([
            'siswa_id' => $validated['siswa_id'],
            'pilihan_bidang' => $validated['pilihan_bidang'],
            'motivasi' => $validated['motivasi'],
        ]);

        $pendaftaran->load(['siswa.kelas', 'siswa.jenisKelamin']);

        // L6: Mengubah status code dari 200 OK menjadi 201 Created sesuai standar REST API
        return response()->json([
            'message' => 'Pendaftaran berhasil dibuat.',
            'data' => new PendaftaranOsisResource($pendaftaran),
        ], 201); 
    }

    /**
     * Memperbarui data pendaftaran OSIS yang sudah ada.
     */
    public function update(Request $request, string|int $id): JsonResponse
    {
        $pendaftaran = PendaftaranOsis::find($id);

        if (! $pendaftaran) {
            return response()->json([
                'message' => 'Pendaftaran tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            // L7: Pengecualian record sendiri saat edit siswa_id
            'siswa_id' => [
                'required',
                'integer',
                'exists:siswa,id',
                'unique:pendaftaran_osis,siswa_id,' . $pendaftaran->id,
            ],
            'pilihan_bidang' => ['required', 'string', 'in:kepemimpinan,keagamaan,olahraga,seni'],
            'motivasi' => ['required', 'string', 'min:20', 'max:300'],
        ]);

        // L8: Memastikan data benar-benar diperbarui ke database
        $pendaftaran->update([
            'siswa_id' => $validated['siswa_id'],
            'pilihan_bidang' => $validated['pilihan_bidang'],
            'motivasi' => $validated['motivasi'],
        ]);

        $pendaftaran->load(['siswa.kelas', 'siswa.jenisKelamin']);

        return response()->json([
            'message' => 'Pendaftaran berhasil diperbarui.',
            'data' => new PendaftaranOsisResource($pendaftaran),
        ], 200);
    }

    /**
     * Menghapus data pendaftaran OSIS.
     */
    public function destroy(string|int $id): JsonResponse|Response
    {
        $pendaftaran = PendaftaranOsis::find($id);

        if (! $pendaftaran) {
            return response()->json([
                'message' => 'Pendaftaran tidak ditemukan.',
            ], 404);
        }

        // L9: Eksekusi hapus data dari database
        $pendaftaran->delete();

        // Mengembalikan status 204 No Content sesuai spesifikasi
        return response()->noContent();
    }
}