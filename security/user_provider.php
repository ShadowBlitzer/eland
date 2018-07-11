<?php declare(strict_types=1);

namespace security;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use service\user;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class user_provider implements UserProviderInterface
{
	private $db;
	private $xdb;

	public function __construct(db $db, xdb $xdb)
	{
		$this->db = $db;
		$this->xdb = $xdb;
	}

	/**
	 * @param string username is actually email (user), username or code
	 * @return user
	 */

    public function loadUserByUsername($username)
    {
        $data = $this->xdb->get('user', $username);

        if ($data === '{}')
        {
			throw new UsernameNotFoundException(
				sprintf('Username "%s" does not exist.', $username)
			);
        }

		$data = json_decode($data, true);

		return new user($username, $data['password'], $data['salt'], $data['roles']);
    }


    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof user)
		{
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return user::class === $class;
    }
}
