namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Config;

class ExampleController extends Controller
{
public function updateWithLock()
{
// Mendapatkan database connection
$db = \Config\Database::connect();

// Memulai transaksi
$db->transStart();

// Menggunakan raw query dengan FOR UPDATE untuk mengunci baris
$builder = $db->table('orders');
$query = $builder->select('*')->where('order_id', 1001)->getCompiledSelect();

// Melakukan query dengan lock
$db->query($query . ' FOR UPDATE');

// Update data setelah mengunci
$db->table('orders')->where('order_id', 1001)->update(['status' => 'processing']);

// Menyelesaikan transaksi
$db->transComplete();

// Cek jika transaksi berhasil
if ($db->transStatus() === false) {
// Rollback jika ada kesalahan
return "Terjadi kesalahan saat transaksi.";
}

return "Update berhasil!";
}
}