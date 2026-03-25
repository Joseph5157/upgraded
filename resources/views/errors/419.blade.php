@include('errors.layout', [
    'status' => 419,
    'title' => 'Session expired',
    'message' => 'Your session timed out for security. Please refresh the page and try again.',
    'secondaryText' => 'Log In',
    'secondaryUrl' => route('login'),
])
