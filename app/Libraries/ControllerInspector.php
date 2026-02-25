<?php

namespace App\Libraries;

use ReflectionClass;

class ControllerInspector
{
    // Mendapatkan seluruh method milik class + doc comment
    public function getClassMethods(string $className, bool $withComment = false): array
    {
        $fullClass = "App\\Controllers\\Api\\{$className}";

        if (!class_exists($fullClass)) {
            return [];
        }

        $reflection = new ReflectionClass($fullClass);
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            // hanya method milik class itu sendiri
            if ($method->class === $fullClass) {

                $item = ['name' => $method->name];

                // if ($withComment) {
                //     $doc = $method->getDocComment();
                //     $item['docComment'] = $this->parseDocComment($doc);
                // }
                if ($withComment) {
                    $item['docComment'] = $this->getMethodComment($reflection, $method->name);
                }

                $methods[] = $item;
            }
        }

        return $methods;
    }

    // Parse doc comment menjadi array
    public function parseDocComment(?string $doc): array
    {
        if (!$doc) return [];

        $lines = explode("\n", $doc);
        $output = [];

        foreach ($lines as $line) {
            $line = trim(str_replace(['/**', '*/', '*'], '', $line));
            if ($line === '') continue;

            if (strpos($line, '@') === 0) {
                [$key, $value] = array_pad(explode(' ', substr($line, 1), 2), 2, '');
                $output[$key][] = trim($value);
            }
        }

        return $output;
    }

    public function getMethodComment(ReflectionClass $class, string $method): array
    {
        // ambil komentar docblock
        $comment = $class->getMethod($method)->getDocComment();
        if (!$comment) {
            return [];
        }

        // regex: tangkap @Tag dan isinya sampai sebelum tag berikutnya
        $pattern = "/@([a-zA-Z0-9_]+)([^\n@]*)/";

        preg_match_all($pattern, $comment, $matches, PREG_SET_ORDER);

        $comments = [];

        foreach ($matches as $match) {
            $tag   = trim($match[1]);
            $value = trim($match[2]);

            if (!isset($comments[$tag])) {
                $comments[$tag] = []; // bisa lebih dari 1 value/tag
            }

            if ($value !== '') {
                $comments[$tag][] = $value;
            }
        }

        return $comments;
    }


    // Menemukan class PHP di dalam file
    public function getPhpClasses(string $phpCode): array
    {
        $tokens = token_get_all($phpCode);
        $classes = [];

        for ($i = 2; $i < count($tokens); $i++) {
            if (
                $tokens[$i - 2][0] === T_CLASS &&
                $tokens[$i - 1][0] === T_WHITESPACE &&
                $tokens[$i][0] === T_STRING
            ) {
                $classes[] = $tokens[$i][1];
            }
        }
        return $classes;
    }

    // Scan semua controller dalam folder App/Controllers/Api/
    // public function scanAllControllers(): array
    // {
    //     $dir = APPPATH . 'Controllers/Api/';
    //     $files = scandir($dir);
    //     $result = [];

    //     foreach ($files as $file) {

    //         if (!is_file($dir . $file) || !str_ends_with($file, '.php')) {
    //             continue;
    //         }

    //         $phpCode  = file_get_contents($dir . $file);
    //         $classes  = $this->getPhpClasses($phpCode);

    //         foreach ($classes as $class) {

    //             $fullClass = "App\\Controllers\\Api\\{$class}";

    //             if (!class_exists($fullClass)) {
    //                 require_once($dir . $file);
    //             }

    //             /** ambil semua method + doc comment */
    //             $methods = $this->getClassMethods($class, true);

    //             $filtered = [];

    //             foreach ($methods as $m) {

    //                 // ✔ hanya ambil method yg punya tag
    //                 if (
    //                     isset($m['docComment']['ClassName']) ||
    //                     isset($m['docComment']['Keterangan']) ||
    //                     isset($m['docComment']['Detail'])
    //                 ) {

    //                     // ✔ jika @ClassName ADA tapi kosong → isi otomatis dengan nama controller
    //                     if (isset($m['docComment']['ClassName']) && empty($m['docComment']['ClassName'])) {
    //                         $m['docComment']['ClassName'][] = $class;
    //                     }

    //                     $filtered[] = $m;
    //                 }
    //             }


    //             /** FILTER: Ambil hanya method yang memiliki @ClassName */
    //             // $filtered = array_filter($methods, function ($m) {
    //             //     return isset($m['docComment']['ClassName']) 
    //             //             || isset($m['docComment']['Keterangan'])
    //             //             || isset($m['docComment']['Detail']);
    //             // });

    //             // Jika tidak ada satupun method valid → skip class tsb
    //             if (empty($filtered)) {
    //                 continue;
    //             }

    //             $result[] = [
    //                 'class'   => $class,
    //                 'methods' => $filtered
    //                 // 'methods' => $methods
    //             ];
    //         }
    //     }

    //     return $result;
    // }

    // Tambahkan parameter array $usedControllers dengan nilai default kosong
    public function scanAllControllers(array $usedControllers = []): array
    {
        $dir = APPPATH . 'Controllers/Api/';
        $files = scandir($dir);
        $result = [];

        // Normalisasi array ke huruf kecil semua agar pengecekan kebal dari typo kapital
        $usedControllers = array_map(fn($item) => strtolower(ucfirst($item) . 'Controller'), $usedControllers);

        foreach ($files as $file) {
            if (!is_file($dir . $file) || !str_ends_with($file, '.php')) {
                continue;
            }

            $phpCode  = file_get_contents($dir . $file);
            $classes  = $this->getPhpClasses($phpCode);

            foreach ($classes as $class) {

                // FILTER UTAMA: Jika class ini ada di daftar yang sudah dipakai, lewati!
                if (in_array(strtolower($class), $usedControllers)) {
                    continue;
                }

                $fullClass = "App\\Controllers\\Api\\{$class}";

                if (!class_exists($fullClass)) {
                    require_once($dir . $file);
                }

                $methods = $this->getClassMethods($class, true);
                $filtered = [];

                foreach ($methods as $m) {
                    if (
                        isset($m['docComment']['ClassName']) ||
                        isset($m['docComment']['Keterangan']) ||
                        isset($m['docComment']['Detail'])
                    ) {
                        if (isset($m['docComment']['ClassName']) && empty($m['docComment']['ClassName'])) {
                            $m['docComment']['ClassName'][] = $class;
                        }
                        $filtered[] = $m;
                    }
                }

                if (empty($filtered)) {
                    continue;
                }

                $result[] = [
                    'class'   => $class,
                    'methods' => $filtered
                ];
            }
        }

        return $result;
    }

    // Pindai seluruh controller dan tampilkan informasi method + komentar
    public function scanController(string $controllerName): array
    {
        $dir = APPPATH . 'Controllers/Api/';
        $files = scandir($dir);

        $data = [];

        foreach ($files as $file) {
            if (!is_file($dir . $file) || !str_ends_with($file, '.php')) {
                continue;
            }

            $phpCode = file_get_contents($dir . $file);
            $classes = $this->getPhpClasses($phpCode);

            foreach ($classes as $class) {
                if ($class === $controllerName) {

                    // load class jika belum diload
                    $fullClass = "App\\Controllers\\Api\\{$class}";
                    if (!class_exists($fullClass)) {
                        require_once($dir . $file);
                    }

                    $methods = $this->getClassMethods($class, true);

                    foreach ($methods as $method) {
                        $doc = $method['docComment'];

                        if (isset($doc['ClassName'])) {

                            $detail = $doc['Detail'] ?? [''];
                            $keterangan = $doc['Keterangan'][0] ?? '';

                            $data[] = [
                                'class'      => $class,
                                'method'     => $method['name'],
                                'name'       => $method['name'] . ' ' . $class,
                                'detail'     => $detail,
                                'keterangan' => $keterangan,
                            ];
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function incrementKode(string $kode): string
    {
        $lastChar = substr($kode, -1);
        $base     = substr($kode, 0, -1);

        $numbers  = ['1', '2', '3', '4', '5', '6', '7', '8'];

        // 1–8 → naik angka
        if (in_array($lastChar, $numbers)) {
            return $base . ((int)$lastChar + 1);
        }

        // 9 → jadi A
        if ($lastChar === '9') {
            return $base . 'A';
        }

        // A–Y → next char
        if (ctype_alpha($lastChar) && $lastChar !== 'Z') {
            return $base . chr(ord($lastChar) + 1);
        }

        // Z → tidak bisa naik lagi
        throw new \Exception("Kode sudah mencapai batas maksimal.");
    }
}
