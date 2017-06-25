<?php

namespace security;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use service\schemas;

class permissions
{
    private $attribute_ary = [
		'view'		=> true,
		'edit'		=> true,
		'create'	=> true,
		'delete'	=> true,
		'undelete'	=> true,
    ];

    private $access_bitmap = [
		'view'		=> 1,
		'edit'		=> 2,
		'create'	=> 4,
		'delete'	=> 8,
		'undelete'	=> 16,
	];

    private $schemas;

    public function __construct(schemas $schemas)
    {
        $this->schemas = $schemas;
    }

	public function vote(TokenInterface $token, $object, array $attributes)
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
				return self::ACCESS_GRANTED;
			}
			else
			{
				return self::ACCESS_DENIED;
			}
		}

        return self::ACCESS_ABSTAIN;
    }
}
