@extends('admin.layouts.app')

@section('title', $title)

@section('content')
<div class="container-fluid pt-4 px-4">
    <div class="bg-light rounded h-100 p-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-1">{{ $title }}</h6>
                @isset($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endisset
            </div>
            @isset($createRoute)
                <a class="btn btn-primary" href="{{ $createRoute }}"><i class="fa fa-plus me-2"></i>Thêm mới</a>
            @endisset
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        @foreach ($headers as $header)
                            <th scope="col">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            @foreach ($row as $cell)
                                <td>
                                    @if ($cell instanceof \Illuminate\Contracts\Support\Htmlable)
                                        {!! $cell->toHtml() !!}
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) + 1 }}" class="text-center text-muted py-4">Chưa có dữ liệu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (is_object($rows) && method_exists($rows, 'links'))
            {{ $rows->links() }}
        @endif
    </div>
</div>
@endsection
