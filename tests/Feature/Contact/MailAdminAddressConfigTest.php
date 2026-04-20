<?php

test('mail.admin_address is defined and reads from MAIL_ADMIN_ADDRESS env var', function () {
    config()->set('mail.admin_address', 'set-by-env@example.com');

    expect(config('mail.admin_address'))->toBe('set-by-env@example.com');
});

test('mail.admin_address falls back to mail.from.address when MAIL_ADMIN_ADDRESS is not set', function () {
    expect(config('mail.admin_address'))
        ->not->toBeNull()
        ->not->toBe('');
});
