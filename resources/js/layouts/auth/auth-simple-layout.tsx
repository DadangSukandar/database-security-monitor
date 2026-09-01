import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative min-h-screen overflow-hidden bg-[#080d16] text-[#f4f7fb]">
            {/* Background decoration */}
            <div
                className="pointer-events-none absolute inset-0"
                style={{
                    background:
                        'radial-gradient(circle at 15% 15%, rgba(15,98,254,0.14), transparent 32%), radial-gradient(circle at 85% 85%, rgba(51,177,255,0.08), transparent 30%), linear-gradient(145deg, #080d16 0%, #0d1522 55%, #101a2a 100%)',
                }}
            />

            <div className="relative z-10 grid min-h-screen lg:grid-cols-[1.15fr_0.85fr]">
                {/* LEFT SIDE */}
                <section className="hidden border-r border-[#243247] lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                    <div>
                        <div className="flex items-center gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-[#34445b] bg-[#101827] shadow-lg">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                    className="h-7 w-7 text-[#33b1ff]"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                >
                                    <path
                                        d="M12 3 19 6v5c0 4.6-2.9 8.1-7 10-4.1-1.9-7-5.4-7-10V6l7-3Z"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                    <path
                                        d="m9.5 12 1.7 1.7 3.5-4"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                </svg>
                            </div>

                            <div>
                                <div className="text-lg font-semibold tracking-tight text-white">
                                    Guardium Security Center
                                </div>

                                <div className="mt-1 text-xs tracking-[0.18em] text-[#a6b4c8] uppercase">
                                    Database Security Platform
                                </div>
                            </div>
                        </div>

                        <div className="mt-24 max-w-xl">
                            <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-[#34445b] bg-[#101827]/80 px-3 py-1.5 text-xs text-[#a6c8ff]">
                                <span className="h-2 w-2 rounded-full bg-[#42be65]" />
                                Security Operations Platform
                            </div>

                            <h1 className="max-w-lg text-4xl leading-tight font-semibold tracking-tight text-white xl:text-5xl">
                                Protect your databases.
                                <span className="block text-[#33b1ff]">
                                    Detect threats faster.
                                </span>
                            </h1>

                            <p className="mt-6 max-w-lg text-base leading-7 text-[#a6b4c8]">
                                Monitor database activity, investigate security
                                findings, manage alerts, and respond to threats
                                from one centralized security center.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center justify-between border-t border-[#243247] pt-6 text-xs text-[#7889a0]">
                        <span>Database Security Center</span>

                        <span className="flex items-center gap-2">
                            <span className="h-2 w-2 rounded-full bg-[#42be65]" />
                            System operational
                        </span>
                    </div>
                </section>

                {/* RIGHT SIDE */}
                <main className="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10 lg:px-12">
                    <div className="w-full max-w-md">
                        {/* Mobile brand */}
                        <div className="mb-10 flex items-center gap-3 lg:hidden">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#34445b] bg-[#101827]">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                    className="h-6 w-6 text-[#33b1ff]"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                >
                                    <path
                                        d="M12 3 19 6v5c0 4.6-2.9 8.1-7 10-4.1-1.9-7-5.4-7-10V6l7-3Z"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                    <path
                                        d="m9.5 12 1.7 1.7 3.5-4"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                </svg>
                            </div>

                            <div>
                                <div className="font-semibold text-white">
                                    Guardium Security Center
                                </div>

                                <div className="text-xs text-[#a6b4c8]">
                                    Database Security Platform
                                </div>
                            </div>
                        </div>

                        <div className="mb-8">
                            <div className="mb-3 text-xs font-semibold tracking-[0.18em] text-[#33b1ff] uppercase">
                                Secure access
                            </div>

                            <h2 className="text-3xl font-semibold tracking-tight text-white">
                                {title}
                            </h2>

                            {description && (
                                <p className="mt-3 text-sm leading-6 text-[#a6b4c8]">
                                    {description}
                                </p>
                            )}
                        </div>

                        <div className="rounded-xl border border-[#34445b] bg-[#101827]/95 p-6 shadow-2xl shadow-black/20 sm:p-8">
                            {children}
                        </div>

                        <div className="mt-6 text-center text-xs text-[#7889a0]">
                            Authorized users only · Security monitored
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
