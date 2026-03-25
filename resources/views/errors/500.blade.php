@include('errors.layout', [
    'status' => 500,
    'title' => 'Something went wrong',
    'message' => 'We hit an internal issue. Please try again in a moment.',
])
