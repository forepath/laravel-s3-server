<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult>
@foreach ($files as $file)
    <Contents>
        <Key>{{ $file }}</Key>
    </Contents>
@endforeach
</ListBucketResult>
