<?php

namespace App\Services\Sms;

class SmsTemplateRenderer
{
    public function render(string $body, array $context = []): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_\.]+)\s*}}/', function (array $matches) use ($context): string {
            $key = $matches[1];
            $value = $this->resolve($context, $key);

            return is_scalar($value) ? (string) $value : '';
        }, $body) ?? $body;
    }

    protected function resolve(array $context, string $key): mixed
    {
        $value = $context;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
