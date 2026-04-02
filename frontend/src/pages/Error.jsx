import { useNavigate, useRouteError } from 'react-router';

//for 404
export function NotFound() {
    const navigate = useNavigate();

    return (
        <div style={{
            height: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center',
            padding: '40px 28px',
            position: 'relative',
            overflow: 'hidden',
        }}>
            {/* Background glow */}
            <div style={{
                position: 'absolute',
                top: '-200px',
                left: '50%',
                transform: 'translateX(-50%)',
                width: '600px',
                height: '600px',
                background: 'radial-gradient(circle, rgba(91,141,239,0.12) 0%, transparent 65%)',
                pointerEvents: 'none',
            }} />

            {/* Floating code block */}
            <div style={{
                fontFamily: 'var(--mono)',
                fontSize: '5rem',
                fontWeight: 700,
                color: 'var(--text-muted)',
                letterSpacing: '-4px',
                lineHeight: 1,
                marginBottom: 8,
                animation: 'float 4s ease-in-out infinite',
                position: 'relative',
                zIndex: 1,
            }}>
                404
            </div>

            <div style={{
                fontFamily: 'var(--mono)',
                fontSize: '0.75rem',
                color: 'var(--blue)',
                marginBottom: 28,
                background: 'var(--blue-subtle)',
                padding: '4px 12px',
                borderRadius: 6,
                position: 'relative',
                zIndex: 1,
            }}>
                PAGE_NOT_FOUND
            </div>

            <h2 style={{
                fontSize: '1.4rem',
                fontWeight: 700,
                marginBottom: 10,
                position: 'relative',
                zIndex: 1,
            }}>
                This page doesn't exist
            </h2>

            <p style={{
                fontSize: '0.92rem',
                color: 'var(--text-secondary)',
                maxWidth: 380,
                lineHeight: 1.6,
                marginBottom: 32,
                position: 'relative',
                zIndex: 1,
            }}>
                The page you're looking for may have been moved, deleted, or never existed.
                Head back home to find what you need.
            </p>

            <div style={{ display: 'flex', gap: 10, position: 'relative', zIndex: 1 }}>
                <button className="btn btn-primary" onClick={() => navigate('/')}>
                     Go Home
                </button>
                <button className="btn btn-secondary" onClick={() => navigate(-1)}>
                    ← Go Back
                </button>
            </div>
        </div>
    );
}

//
export function ErrorBoundaryPage() {
    const error = useRouteError();
    const navigate = useNavigate();

    const status = error?.status || error?.response?.status;
    const is401 = status === 401;
    const is403 = status === 403;
    const is500 = status >= 500;

    const config = is401
        ? {
            code: '401',
            tag: 'UNAUTHORIZED',
            tagColor: 'var(--orange)',
            tagBg: 'var(--orange-subtle)',
            glowColor: 'rgba(234,178,68,0.12)',
            title: 'Sign in required',
            message: 'You need to be signed in to view this page.',
            icon: '',
        }
        : is403
            ? {
                code: '403',
                tag: 'FORBIDDEN',
                tagColor: 'var(--red)',
                tagBg: 'var(--red-subtle)',
                glowColor: 'rgba(224,90,90,0.12)',
                title: 'Access denied',
                message: "You don't have permission to view this page.",
                icon: '',
            }
            : is500
                ? {
                    code: '500',
                    tag: 'SERVER_ERROR',
                    tagColor: 'var(--red)',
                    tagBg: 'var(--red-subtle)',
                    glowColor: 'rgba(224,90,90,0.12)',
                    title: 'Something went wrong',
                    message: 'The server ran into an unexpected error. Try again in a moment.',
                    icon: '',
                }
                : {
                    code: 'ERR',
                    tag: 'UNEXPECTED_ERROR',
                    tagColor: 'var(--text-muted)',
                    tagBg: 'var(--bg-elevated)',
                    glowColor: 'rgba(91,141,239,0.1)',
                    title: 'An error occurred',
                    message: error?.message || 'Something unexpected happened.',
                    icon: '',
                };

    return (
        <div style={{
            height: '100%',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center',
            padding: '40px 28px',
            position: 'relative',
            overflow: 'hidden',
        }}>
            <div style={{
                position: 'absolute',
                top: '-200px',
                left: '50%',
                transform: 'translateX(-50%)',
                width: '600px',
                height: '600px',
                background: `radial-gradient(circle, ${config.glowColor} 0%, transparent 65%)`,
                pointerEvents: 'none',
            }} />

            <div style={{ fontSize: '2.8rem', marginBottom: 16, animation: 'float 4s ease-in-out infinite', position: 'relative', zIndex: 1 }}>
                {config.icon}
            </div>

            <div style={{
                fontFamily: 'var(--mono)',
                fontSize: '3.5rem',
                fontWeight: 700,
                color: 'var(--text-muted)',
                letterSpacing: '-2px',
                lineHeight: 1,
                marginBottom: 8,
                position: 'relative',
                zIndex: 1,
            }}>
                {config.code}
            </div>

            <div style={{
                fontFamily: 'var(--mono)',
                fontSize: '0.72rem',
                color: config.tagColor,
                marginBottom: 24,
                background: config.tagBg,
                padding: '4px 12px',
                borderRadius: 6,
                position: 'relative',
                zIndex: 1,
            }}>
                {config.tag}
            </div>

            <h2 style={{ fontSize: '1.4rem', fontWeight: 700, marginBottom: 10, position: 'relative', zIndex: 1 }}>
                {config.title}
            </h2>

            <p style={{
                fontSize: '0.92rem',
                color: 'var(--text-secondary)',
                maxWidth: 380,
                lineHeight: 1.6,
                marginBottom: 32,
                position: 'relative',
                zIndex: 1,
            }}>
                {config.message}
            </p>

            {/* Show raw error details in dev mode only */}
            {import.meta.env.DEV && error?.message && (
                <div style={{
                    background: 'var(--bg-card)',
                    border: '1px solid var(--border)',
                    borderRadius: 'var(--radius)',
                    padding: '12px 16px',
                    marginBottom: 24,
                    maxWidth: 480,
                    width: '100%',
                    textAlign: 'left',
                    position: 'relative',
                    zIndex: 1,
                }}>
                    <div style={{ fontFamily: 'var(--mono)', fontSize: '0.72rem', color: 'var(--text-muted)', marginBottom: 6 }}>
                        DEV — error details
                    </div>
                    <div style={{ fontFamily: 'var(--mono)', fontSize: '0.78rem', color: 'var(--red)', wordBreak: 'break-all' }}>
                        {error.message}
                    </div>
                    {error.stack && (
                        <div style={{ fontFamily: 'var(--mono)', fontSize: '0.7rem', color: 'var(--text-muted)', marginTop: 8, whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
                            {error.stack.split('\n').slice(1, 4).join('\n')}
                        </div>
                    )}
                </div>
            )}

            <div style={{ display: 'flex', gap: 10, position: 'relative', zIndex: 1 }}>
                <button className="btn btn-primary" onClick={() => navigate('/')}>
                     Go Home
                </button>
                <button className="btn btn-secondary" onClick={() => navigate(-1)}>
                    ← Go Back
                </button>
                {(is500 || !status) && (
                    <button className="btn btn-secondary" onClick={() => window.location.reload()}>
                        ↻ Retry
                    </button>
                )}
            </div>
        </div>
    );
}
