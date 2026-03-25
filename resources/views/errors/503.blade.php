@include('errors.layout', [
    'status' => 503,
    'title' => 'Service unavailable',
    'message' => 'We are temporarily unavailable, likely for maintenance. Please try again soon.',
])
