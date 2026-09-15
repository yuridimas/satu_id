<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $exception->getStatusCode() }} - {{ $exception->getMessage() }}</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.5;
                color: #212529;
                text-align: center;
                background-color: #f8f9fa;
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 600px;
                margin: 100px auto;
                padding: 0 15px;
            }

            h1 {
                font-size: 6rem;
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 0.5rem;
                color: #dc3545;
            }

            p {
                font-size: 1.5rem;
                color: #6c757d;
                margin-bottom: 1.5rem;
            }

            a {
                color: #0d6efd;
                text-decoration: none;
            }

            a:hover {
                text-decoration: underline;
            }

            .btn {
                display: inline-block;
                font-weight: 400;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                user-select: none;
                border: 1px solid transparent;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: 0.25rem;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            }

            .btn-primary {
                color: #fff;
                background-color: #0d6efd;
                border-color: #0d6efd;
            }

            .btn-primary:hover {
                background-color: #0b5ed7;
                border-color: #0a58ca;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>{{ $exception->getStatusCode() }}</h1>
            <p>{{ $exception->getMessage() }}</p>
            <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Go Home') }}</a>
        </div>
    </body>
</html>