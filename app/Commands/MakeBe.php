<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeBe extends BaseCommand
{
  protected $group       = 'Custom';
  protected $name        = 'make:be';
  protected $description = 'Generate Controller, Model, and Service';

  public function run(array $params)
  {
    if (empty($params)) {
      CLI::error("Masukkan nama module. Contoh: php spark make:be User");
      return;
    }

    $name = ucfirst($params[0]);

    $this->makeController($name);
    $this->makeModel($name);
    $this->makeService($name);

    CLI::write("✅ Backend $name berhasil dibuat!", 'green');
  }

  private function makeController($name)
  {
    $path = APPPATH . "Controllers/Api/{$name}Controller.php";

    $template = str_replace(
      ['{name}', '{var}'],
      [$name, strtolower($name)],
      file_get_contents(APPPATH . 'Commands/Templates/controller.tpl')
    );

    file_put_contents($path, $template);
    CLI::write("✔ Controller dibuat: $path");
  }

  private function makeModel($name)
  {
    $path = APPPATH . "Models/{$name}.php";

    $template = str_replace(
      ['{name}', '{table}'],
      [$name, strtolower($name) . 's'],
      file_get_contents(APPPATH . 'Commands/Templates/model.tpl')
    );

    file_put_contents($path, $template);
    CLI::write("✔ Model dibuat: $path");
  }

  private function makeService($name)
  {
    $path = APPPATH . "Services/{$name}Service.php";

    $template = str_replace(
      ['{name}', '{var}'],
      [$name, strtolower($name)],
      file_get_contents(APPPATH . 'Commands/Templates/service.tpl')
    );

    file_put_contents($path, $template);
    CLI::write("✔ Service dibuat: $path");
  }
}
