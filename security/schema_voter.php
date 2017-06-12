<?php

namespace security;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use service\schemas;

class schema_voter implements VoterInterface
{
    private $attribute_ary = [
		'AC_GUEST_INTERLETS'	=> true,
		'AC_GUEST_ELAS'			=> true,
		'AC_GUEST_ELAND'		=> true,
		'AC_INTERLETS'			=> true,
		'AC_USER'				=> true,
		'AC_ADMIN'				=> true,
		'AC_MASTER'				=> true,
    ];

    private $schemas;

    public function __construct(schemas $schemas)
    {
        $this->schemas = $schemas;
    }

    public function supports_attribute($attribute)
    {
        return self::ROLE_ALLOWED_ON_DOMAIN === $attribute;
    }

    public function supports_class($class)
    {
        return true;
    }

	public function vote(TokenInterface $token, Request $request, array $attributes)
	{
/*
		if (!($object instanceof Request))
		{
			return self::ACCESS_ABSTAIN;
		}
*/
		$user = $token->getUser();

		if (!($user instanceof UserInterface))
		{
			return self::ACCESS_ABSTAIN;
		}

		$route_params = $request->attributes->get('_route_params');

		if (!isset($schema = $route_params['schema']))
		{
			return self::ACCESS_ABSTAIN;
		}

		if (!isset($role = $route_params['role']))
		{
			return self::ACCESS_ABSTAIN;
		}




		if (!isset($guest_type = $route_params['guest_type']))
		{
			return self::ACCESS_ABSTAIN;
		}


		foreach ($attributes as $attribute)
		{
			if (!$this->supports_attribute($attribute))
			{
				continue;
			}

			$host = $object->getHost();

			if ($this->decisionManager->decide($user, $host))
			{
				$result = self::ACCESS_GRANTED;
			}
			else
			{
				$result = self::ACCESS_DENIED;
			}
		}

        return self::ACCESS_ABSTAIN;
    }
}
