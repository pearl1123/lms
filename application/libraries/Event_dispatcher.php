<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal event dispatcher (additive, backward-compatible).
 */
class Event_dispatcher {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Dispatch event to configured listeners.
     *
     * @param string $event_name
     * @param array  $payload
     * @return int Number of invoked listeners
     */
    public function dispatch($event_name, array $payload = [])
    {
        $map = $this->listener_map();
        $listeners = isset($map[$event_name]) ? (array) $map[$event_name] : [];
        $invoked = 0;

        foreach ($listeners as $listener_sig) {
            if ( ! is_string($listener_sig) || strpos($listener_sig, '@') === false) {
                continue;
            }
            list($class, $method) = explode('@', $listener_sig, 2);
            $class = trim($class);
            $method = trim($method);
            if ($class === '' || $method === '') {
                continue;
            }

            $prop = strtolower($class);
            if ( ! isset($this->CI->{$prop})) {
                $this->CI->load->library($class, null, $prop);
            }
            $obj = isset($this->CI->{$prop}) ? $this->CI->{$prop} : null;
            if ($obj && method_exists($obj, $method)) {
                call_user_func([$obj, $method], $payload);
                $invoked++;
            }
        }

        return $invoked;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function listener_map()
    {
        $path = APPPATH . 'config/event_listeners.php';
        if ( ! is_file($path)) {
            return [];
        }
        $map = include $path;

        return is_array($map) ? $map : [];
    }
}

