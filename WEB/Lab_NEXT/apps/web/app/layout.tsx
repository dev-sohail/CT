import type { Metadata } from 'next';
import '@ctlab/ctlab-theme/globals.css';
import '@ctlab/ctlab-ui/styles.css';
import './globals.css';
import { ThemeProvider, noFlashScript } from '@ctlab/ctlab-theme';
import AuthGuard from '@/components/AuthGuard';

export const metadata: Metadata = {
    title: 'CTLabs',
    description: 'CTLabs — Personal Software Ecosystem',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
    return (
        <html lang="en">
            <head>
                <script dangerouslySetInnerHTML={{ __html: noFlashScript }} />
            </head>
            <body>
                <ThemeProvider>
                    <AuthGuard>{children}</AuthGuard>
                </ThemeProvider>
            </body>
        </html>
    );
}
