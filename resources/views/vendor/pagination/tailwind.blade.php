@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-y-4 items-center justify-center flex-col sm:flex-row sm:justify-between display lowercase">

            <div>
                <p class="text-sm text-gray-700 leading-5 dark:text-gray-400">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div class="join font-xs">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="btn btn-xs join-item px-4 btn-outline btn-disabled" >
                            «
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-xs px-4 btn-outline" aria-label="{{ __('pagination.previous') }}">
                            «
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="flex items-center px-4 text-primary-content" style='border: 1px solid currentColor'>{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" style='border: 1px solid currentColor;' class="bg-primary text-primary-content border-primary-content text-xs px-4 flex items-center">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="btn btn-xs px-4 join-item btn-outline" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn px-4 btn-xs join-item btn-outline" aria-label="{{ __('pagination.next') }}">
                            »
                        </a>
                    @else
                            <span class="btn btn-disabled btn-xs px-4 join-item btn-outline" aria-hidden="true">
                                »
                            </span>
                    @endif
            </div>
    </nav>
@endif
