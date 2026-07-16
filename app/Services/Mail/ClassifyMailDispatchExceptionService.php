<?php

namespace App\Services\Mail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class ClassifyMailDispatchExceptionService
{
    public function isTransient(Throwable $throwable): bool
    {
        return ! $this->isPermanent($throwable);
    }

    public function isPermanent(Throwable $throwable): bool
    {
        $diagnostic = $this->diagnosticText($throwable);

        foreach ($this->permanentPatterns() as $pattern) {
            if (str_contains($diagnostic, $pattern)) {
                return true;
            }
        }

        return preg_match('/\b5\.1\.[0-9]\b/', $diagnostic) === 1
            || preg_match('/\b5\.2\.2\b/', $diagnostic) === 1
            || preg_match('/\b55[013]\b/', $diagnostic) === 1
            || preg_match('/\b552\b/', $diagnostic) === 1
            || preg_match('/\b53[045]\b/', $diagnostic) === 1;
    }

    public function diagnosticMessage(Throwable $throwable): string
    {
        $message = trim($throwable->getMessage());

        if ($message !== '') {
            return $message;
        }

        if ($throwable instanceof TransportExceptionInterface) {
            $debug = trim($throwable->getDebug());

            if ($debug !== '') {
                return $debug;
            }
        }

        return $throwable::class;
    }

    private function diagnosticText(Throwable $throwable): string
    {
        $messages = [];
        $current = $throwable;

        while ($current instanceof Throwable) {
            $messages[] = $current->getMessage();

            if ($current instanceof TransportExceptionInterface) {
                $messages[] = $current->getDebug();
            }

            $current = $current->getPrevious();
        }

        return mb_strtolower(implode("\n", array_filter($messages)));
    }

    /**
     * @return list<string>
     */
    private function permanentPatterns(): array
    {
        return [
            'authentication failed',
            'authenticate',
            'bad destination mailbox address',
            'does not exist',
            'invalid address',
            'invalid credentials',
            'invalid recipient',
            'exceeded storage allocation',
            'insufficient storage',
            'mailbox full',
            'mailbox is full',
            'mailbox not found',
            'mailbox size limit exceeded',
            'mailbox storage limit exceeded',
            'mailbox unavailable',
            'no such user',
            'over quota',
            'quota exceeded',
            'recipient address rejected',
            'recipient inbox full',
            'storage allocation exceeded',
            'unknown user',
            'user unknown',
            'username and password',
        ];
    }
}
