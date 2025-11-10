import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {


    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <h1 className="text-5xl font-bold mb-4 text-gray-900 dark:text-white">
                    Welcome to Event Booking Test
                </h1>
                <p className="text-lg mb-6 text-gray-700 dark:text-gray-300">
                    This is your custom welcome page using React, TypeScript, and ShadCN.
                </p>
            </div>
        </>
    );
}
