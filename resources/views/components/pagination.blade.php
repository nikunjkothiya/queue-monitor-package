@if ($paginator->hasPages())
    <nav class="qm-pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="qm-pagination-list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="qm-pagination-item qm-pagination-disabled">
                    <span class="qm-pagination-link">&laquo;</span>
                </li>
            @else
                <li class="qm-pagination-item">
                    <a class="qm-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="qm-pagination-item qm-pagination-disabled">
                        <span class="qm-pagination-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="qm-pagination-item qm-pagination-active">
                                <span class="qm-pagination-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="qm-pagination-item">
                                <a class="qm-pagination-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="qm-pagination-item">
                    <a class="qm-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
                </li>
            @else
                <li class="qm-pagination-item qm-pagination-disabled">
                    <span class="qm-pagination-link">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>

    <style>
        .qm-pagination {
            display: flex;
            justify-content: center;
        }
        
        .qm-pagination-list {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .qm-pagination-item {
            margin: 0;
            padding: 0;
        }
        
        .qm-pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            background: var(--bg-card, #1a1a2e);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-sm, 8px);
            color: var(--text-secondary, #a0a0b8);
            text-decoration: none;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        
        .qm-pagination-link:hover {
            background: var(--bg-tertiary, #16213e);
            border-color: var(--border-color-light, rgba(255, 255, 255, 0.12));
            color: var(--text-primary, #ffffff);
            text-decoration: none;
        }
        
        .qm-pagination-active .qm-pagination-link {
            background: var(--accent-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
            border-color: transparent;
            color: white;
            cursor: default;
        }
        
        .qm-pagination-disabled .qm-pagination-link {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
@endif
