<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (!empty($input)) {
            $request->merge($this->sanitize($input, $this->except));
        }

        return $next($request);
    }

    protected function sanitize(array $input, array $except = []): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (in_array($key, $except, true)) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value, $except);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->clean($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function clean(string $value): string
    {
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        $value = preg_replace('/javascript\s*:/i', '', $value);

        $value = preg_replace('/data\s*:/i', '', $value);

        $value = preg_replace('/vbscript\s*:/i', '', $value);

        $value = preg_replace('/on\w+\s*=/i', '', $value);

        return $value;
    }
}
