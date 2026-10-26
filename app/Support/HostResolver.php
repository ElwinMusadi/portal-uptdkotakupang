<?php

namespace App\Support;

/**
 * The IP addresses of a host name, so PublicPageFetcher can refuse internal
 * addresses before connecting and then connect to exactly the address it
 * checked. A separate class so tests resolve without DNS.
 */
class HostResolver
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = is_array($records)
            ? array_map(fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null, $records)
            : [];

        if (array_filter($addresses) === []) {
            $addresses = @gethostbynamel($host) ?: [];
        }

        return array_values(array_unique(array_filter($addresses, 'is_string')));
    }
}
