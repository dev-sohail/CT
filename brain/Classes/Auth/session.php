<?php
/**
 * Class Session
 *
 * Enhanced PHP session wrapper with built-in protection against
 * session hijacking by binding to IP and User-Agent, and periodic
 * session ID regeneration.
 */
class Session {
    /**
     * Stores session data, linked to $_SESSION.
     *
     * @var array
     */
    public array $data = [];

    /**
     * Security configuration.
     *
     * @var array
     */
    protected array $config = [
        'check_ip'          => true,
        'check_user_agent'  => true,
        'regenerate_time'   => 300, // seconds (e.g., 5 minutes)
    ];

    /**
     * Initializes the PHP session and links `$this->data` to `$_SESSION`.
     * Also sets up security bindings.
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);

        if (!session_id() && !headers_sent()) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }

        $this->data =& $_SESSION;
        $this->initializeSecurity();
    }

    /**
     * Explicitly start the session if not started.
     */
    public function start(): void {
        if (!session_id()) {
            session_start();
            $this->data =& $_SESSION;
        }
    }

    /**
     * Returns the current session ID.
     *
     * @return string
     */
    public function getId(): string {
        return session_id();
    }

    /**
     * Destroys the current session and clears session data.
     */
    public function destroy(): void {
        if (session_id()) {
            session_unset();
            session_destroy();
            $this->data = [];
        }
    }

    /**
     * Initialize session hijack protection.
     */
    protected function initializeSecurity(): void {
        if (!isset($this->data['_secure'])) {
            $this->data['_secure'] = [
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created'    => time(),
                'last_regen' => time(),
            ];
        }

        if (!$this->isValid()) {
            $this->destroy();
            session_regenerate_id(true);
            $this->start();
            $this->initializeSecurity();
        } elseif ($this->shouldRegenerate()) {
            session_regenerate_id(true);
            $this->data['_secure']['last_regen'] = time();
        }
    }

    /**
     * Validate current session against hijack attempts.
     */
    protected function isValid(): bool {
        $secure = $this->data['_secure'];

        if ($this->config['check_ip'] && ($_SERVER['REMOTE_ADDR'] ?? '') !== $secure['ip']) {
            return false;
        }

        if ($this->config['check_user_agent'] && ($_SERVER['HTTP_USER_AGENT'] ?? '') !== $secure['user_agent']) {
            return false;
        }

        return true;
    }

    /**
     * Determine if session ID should be regenerated.
     */
    protected function shouldRegenerate(): bool {
        return time() - $this->data['_secure']['last_regen'] > $this->config['regenerate_time'];
    }
}
