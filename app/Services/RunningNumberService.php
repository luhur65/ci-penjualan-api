<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * RunningNumberService
 *
 * Service reusable untuk generate nomor urut (running number) otomatis.
 *
 * Pola nomor: {PREFIX}{SEPARATOR}{NOMOR_URUT_PADDED}
 * Contoh     : INV-0000001, SO-00001, TRX-000001
 *
 * Cara pakai:
 *   $svc = new RunningNumberService();
 *   $no  = $svc->generate('tbl_penjualan', 'no_bukti', 'INV', '-', 7);
 */
class RunningNumberService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Generate nomor urut berikutnya.
     *
     * @param  string $table     Nama tabel yang menyimpan kolom nomor
     * @param  string $column    Nama kolom nomor (misal: no_bukti)
     * @param  string $prefix    Prefix nomor (misal: INV, SO, TRX)
     * @param  string $separator Pemisah antara prefix dan angka (misal: -, /)
     * @param  int    $pad       Panjang angka dengan leading zero (misal: 7 → 0000001)
     * @param  string $where     Kondisi WHERE tambahan (opsional, misal: "tahun = 2024")
     * @return string            Nomor berikutnya, misal: INV-0000001
     */
    public function generate(
        string $table,
        string $column    = 'no_bukti',
        string $prefix    = 'INV',
        string $separator = '-',
        int    $pad       = 7,
        string $where     = ''
    ): string {
        // Lock tabel supaya tidak terjadi race condition (concurrent request)
        $this->db->query("LOCK TABLES `{$table}` WRITE");

        try {
            $likePrefix = $prefix . $separator;

            $builder = $this->db->table($table)
                ->select("MAX(CAST(SUBSTRING(`{$column}`, " . (strlen($likePrefix) + 1) . ") AS UNSIGNED)) AS last_num")
                ->like($column, $likePrefix, 'after');

            if ($where) {
                $builder->where($where);
            }

            $row     = $builder->get()->getRowArray();
            $lastNum = (int)($row['last_num'] ?? 0);
            $nextNum = $lastNum + 1;

            return $prefix . $separator . str_pad($nextNum, $pad, '0', STR_PAD_LEFT);

        } finally {
            $this->db->query("UNLOCK TABLES");
        }
    }

    /**
     * Generate nomor urut dengan periode tahun-bulan (opsional).
     *
     * @param  string $table
     * @param  string $column
     * @param  string $prefix
     * @param  string $separator
     * @param  int    $pad
     * @param  bool   $withYear      Sertakan tahun dalam prefix (misal: INV/2024/)
     * @param  bool   $withMonth     Sertakan bulan dalam prefix (misal: INV/2024/06/)
     * @param  string|null $dateCol  Kolom tanggal untuk filter periode (misal: tgl_bukti)
     * @return string
     */
    public function generateWithPeriod(
        string $table,
        string $column    = 'no_bukti',
        string $prefix    = 'INV',
        string $separator = '/',
        int    $pad       = 5,
        bool   $withYear  = true,
        bool   $withMonth = false,
        ?string $dateCol  = null
    ): string {
        $year  = date('Y');
        $month = date('m');

        // Bangun prefix dengan periode
        $fullPrefix = $prefix . $separator . $year;
        if ($withMonth) {
            $fullPrefix .= $separator . $month;
        }
        $fullPrefix .= $separator;

        // WHERE untuk filter periode
        $where = '';
        if ($dateCol) {
            if ($withMonth) {
                $where = "YEAR(`{$dateCol}`) = {$year} AND MONTH(`{$dateCol}`) = {$month}";
            } else {
                $where = "YEAR(`{$dateCol}`) = {$year}";
            }
        }

        $this->db->query("LOCK TABLES `{$table}` WRITE");

        try {
            $builder = $this->db->table($table)
                ->select("MAX(CAST(SUBSTRING(`{$column}`, " . (strlen($fullPrefix) + 1) . ") AS UNSIGNED)) AS last_num")
                ->like($column, $fullPrefix, 'after');

            if ($where) {
                $builder->where($where);
            }

            $row     = $builder->get()->getRowArray();
            $lastNum = (int)($row['last_num'] ?? 0);
            $nextNum = $lastNum + 1;

            return $fullPrefix . str_pad($nextNum, $pad, '0', STR_PAD_LEFT);

        } finally {
            $this->db->query("UNLOCK TABLES");
        }
    }
}
