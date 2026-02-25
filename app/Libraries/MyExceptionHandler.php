<?php

namespace App\Libraries;

use CodeIgniter\Debug\Exceptions\ExceptionHandler;
use Throwable;

class MyExceptionHandler
{
  public function handle(Throwable $exception, int $code = 500)
  {
    // Log error
    log_message('critical', $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());

    $request = service('request');
    $response = service('response');

    // Jika request API
    if ($request->isAJAX() || str_contains($request->getPath(), 'api/')) {
      $response->setStatusCode($code)
        ->setJSON([
          'status'  => false,
          'message' => $exception->getMessage(),
          'code'    => $code
        ])
        ->send();
      exit;
    }

    // Web biasa
    $defaultHandler = new ExceptionHandler(config('Exceptions'));
    $defaultHandler->handle($exception, $code);
  }
}
