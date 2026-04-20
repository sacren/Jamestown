<?php

use App\Http\Requests\ContactFormRequest;
use Illuminate\Support\Facades\Validator;

function validateContact(array $data): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(
        $data,
        ContactFormRequest::sharedRules(),
        ContactFormRequest::sharedMessages(),
    );
}

test('valid contact form data passes validation', function () {
    $validator = validateContact([
        'name' => 'Jane Trade',
        'email' => 'jane@example.com',
        'subject' => 'Welding program question',
        'message' => 'I would like to know more about the welding program schedule.',
    ]);

    expect($validator->passes())->toBeTrue();
});

test('name is required', function () {
    $validator = validateContact([
        'email' => 'jane@example.com',
        'subject' => 'Question',
        'message' => 'Hello there, I have a question.',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('name'))->not->toBeEmpty();
});

test('email must be valid', function () {
    $validator = validateContact([
        'name' => 'Jane',
        'email' => 'not-an-email',
        'subject' => 'Question',
        'message' => 'Hello there, I have a question.',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('email'))->not->toBeEmpty();
});

test('subject is required', function () {
    $validator = validateContact([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'message' => 'Hello there, I have a question.',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('subject'))->not->toBeEmpty();
});

test('message must be at least 10 characters', function () {
    $validator = validateContact([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'subject' => 'Question',
        'message' => 'Short',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('message'))->toContain('10');
});

test('message is required', function () {
    $validator = validateContact([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'subject' => 'Question',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('message'))->not->toBeEmpty();
});

test('name cannot exceed 120 characters', function () {
    $validator = validateContact([
        'name' => str_repeat('a', 121),
        'email' => 'jane@example.com',
        'subject' => 'Question',
        'message' => 'Hello there, I have a question.',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('name'))->not->toBeEmpty();
});

test('message cannot exceed 3000 characters', function () {
    $validator = validateContact([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'subject' => 'Question',
        'message' => str_repeat('a', 3001),
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->get('message'))->not->toBeEmpty();
});

test('request authorizes public contact form submissions', function () {
    expect((new ContactFormRequest)->authorize())->toBeTrue();
});

test('request rules match shared rules', function () {
    expect((new ContactFormRequest)->rules())
        ->toEqual(ContactFormRequest::sharedRules());
});
