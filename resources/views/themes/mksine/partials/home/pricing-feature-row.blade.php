@props([
    'included' => true,
    'label' => '',
])
<div
    class="{{ $included ? 'hover:bg-green-50/50 dark:hover:bg-green-950/25' : 'opacity-60 hover:bg-gray-50/50 dark:hover:bg-white/5' }} flex items-center gap-2 rounded-lg p-1.5 transition-colors"
>
    <div
        class="{{ $included ? 'bg-green-100 text-green-600 dark:bg-green-900/45 dark:text-green-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }} flex h-4 w-4 shrink-0 items-center justify-center rounded-full"
    >
        @if ($included)
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-2.5 w-2.5"
                aria-hidden="true"
            >
                <path d="M20 6 9 17l-5-5"></path>
            </svg>
        @else
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-2.5 w-2.5"
                aria-hidden="true"
            >
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        @endif
    </div>
    <span
        class="{{ $included ? 'text-gray-700 dark:text-gray-200' : 'text-gray-500 line-through dark:text-gray-500' }} text-xs font-medium"
    >{{ $label }}</span>
</div>
