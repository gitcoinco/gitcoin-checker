<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100"
    >
        <div>
            <slot name="logo" />
        </div>

        <div
            class="w-full sm:max-w-md px-6 mb-12 overflow-hidden sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
