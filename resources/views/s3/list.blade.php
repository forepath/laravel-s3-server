<?xml version="1.0" encoding="UTF-8"?>
<ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
    <Name>{{ $bucket }}</Name>
@if(isset($prefix) && $prefix)
    <Prefix>{{ $prefix }}</Prefix>
@endif
@if(isset($delimiter) && $delimiter)
    <Delimiter>{{ $delimiter }}</Delimiter>
@endif
@if(isset($maxKeys) && $maxKeys)
    <MaxKeys>{{ $maxKeys }}</MaxKeys>
@endif
@if(isset($marker) && $marker)
    <Marker>{{ $marker }}</Marker>
@endif
@if(isset($isTruncated) && $isTruncated)
    <IsTruncated>true</IsTruncated>
@else
    <IsTruncated>false</IsTruncated>
@endif
@if(isset($nextMarker) && $nextMarker)
    <NextMarker>{{ $nextMarker }}</NextMarker>
@endif
@foreach ($files as $index => $file)
    <Contents>
        <Key>{{ $file }}</Key>
        <LastModified>{{ \Carbon\Carbon::createFromTimestamp(Storage::lastModified($fullPaths[$index]))->toISOString() }}</LastModified>
        <ETag>"{{ Storage::checksum($fullPaths[$index]) }}"</ETag>
        <Size>{{ Storage::size($fullPaths[$index]) }}</Size>
        <StorageClass>STANDARD</StorageClass>
        <Owner>
            <ID>0000000000000000000000000000000000000000000000000000000000000000</ID>
            <DisplayName>owner</DisplayName>
        </Owner>
    </Contents>
@endforeach
@foreach ($prefixes as $prefixItem)
    <CommonPrefixes>
        <Prefix>{{ $prefixItem }}</Prefix>
    </CommonPrefixes>
@endforeach
</ListBucketResult>
