<?php

return [
	'class' => 'app\models\Auth',
	'methods' => [
		0 => [
			'class' => 'app\components\Ad',
			// An array of your AD hosts. You can use either
			// the host name or the IP address of your host.
			'name' => 'KSZ',
			'description' => 'KSZ description',
			'domain' => 'kszofingen.local',
			'ldap_uri' => 'ldap://172.10.0.4:389 ldap://172.10.0.15:389',
			'mapping' => [
				'Administratoren' => 'admin',
				'Teacher' => 'teacher',
			],
			'loginScheme' => '{username}@ksz',
			'bindScheme' => '{username}@{domain}',
			'searchFilter' => '(sAMAccountName={username})',
		],
		1 => [
			'class' => 'app\components\Ad',
			// An array of your AD hosts. You can use either
			// the host name or the IP address of your host.
			'name' => 'BWZ',
			'domain' => 'bwzofingen.local',
			'mapping' => [
				'LG-IT-Admins' => 'admin',
				'Ticket-Agent' => 'teacher',
				'LG-Lehrer-BFS' => 'teacher',
				'Administratoren' => 'teacher',
			],
			'loginScheme' => '{username}@bwz',
			'bindScheme' => '{username}@{domain}',
		],
		2 => [
			'class' => 'app\components\Ad',
			'name' => 'LDAP1',
			'description' => 'Test Active Directory connection 1',
			'domain' => 'test.local',
			// An array of your AD hosts. You can use either
			// the host name or the IP address of your host.
			'ldap_uri' => 'ldap://192.168.0.67:389',
			'mapping' => [
				'admins' => 'admin',
				'teachers' => 'teacher',
			],
			'loginScheme' => '{username}',
			'bindScheme' => '{username}@{domain}',
			'searchFilter' => '(sAMAccountName={username})',
		],
		3 => [
			'class' => 'app\components\Ad',
			'name' => 'LDAP2',
			'description' => 'Test Active Directory connection 2',
			'domain' => 'test.local',
			// An array of your AD hosts. You can use either
			// the host name or the IP address of your host.
			'ldap_uri' => 'ldap://192.168.0.67:389',
			'mapping' => [
				'teachers' => 'teacher',
			],
			'loginScheme' => '{username}@teacher',
			'bindScheme' => '{username}@{domain}',
			'searchFilter' => '(sAMAccountName={username})',
		],
	]
];