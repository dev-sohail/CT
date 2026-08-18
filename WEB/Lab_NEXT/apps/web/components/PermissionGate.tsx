'use client';

import { type ReactNode } from 'react';
import { useRBAC } from './RBACProvider';

interface PermissionGateProps {
    permission: string;
    fallback?: ReactNode;
    children: ReactNode;
}

export default function PermissionGate({ permission, fallback = null, children }: PermissionGateProps) {
    const { hasPermission } = useRBAC();
    return hasPermission(permission) ? <>{children}</> : <>{fallback}</>;
}
