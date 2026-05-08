import { Head } from '@inertiajs/react';
import { Coffee } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Welcome() {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-amber-50 to-orange-100 p-6">
                <Card className="w-full max-w-md">
                    <CardHeader className="items-center text-center">
                        <div className="bg-primary/10 text-primary mb-2 flex size-14 items-center justify-center rounded-full">
                            <Coffee className="size-7" />
                        </div>
                        <CardTitle className="text-3xl">Star Coffee</CardTitle>
                        <CardDescription>F&amp;B Platform — Coming Soon</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        <div className="text-muted-foreground flex flex-wrap justify-center gap-2 text-xs">
                            <span className="bg-secondary rounded-full px-3 py-1">Laravel 12</span>
                            <span className="bg-secondary rounded-full px-3 py-1">React 19</span>
                            <span className="bg-secondary rounded-full px-3 py-1">Inertia 2</span>
                            <span className="bg-secondary rounded-full px-3 py-1">Tailwind 4</span>
                            <span className="bg-secondary rounded-full px-3 py-1">PWA</span>
                        </div>
                        <Button className="w-full">Get Started</Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
