<?php declare(strict_types=1);

namespace security;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use service\schemas;

class schema_voter implements VoterInterface
{
    private $attribute_ary = [
		'view'		=> true,
		'edit'		=> true,
		'create'	=> true,
		'delete'	=> true,
		'undelete'	=> true,
    ];

    private $attributes = [
		'view'		=> 1,
		'edit'		=> 2,
		'create'	=> 4,
		'delete'	=> 8,
		'undelete'	=> 16,
	];



    private $schemas;

    public function __construct(schemas $schemas, RequestStack $requestStack)
    {
        $this->schemas = $schemas;
        $this->request = $requestStack->getCurrentRequest();
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

		if (!isset($route_params['schema']))
		{
			return self::ACCESS_ABSTAIN;
		}

		if (!isset($route_params['role']))
		{
			return self::ACCESS_ABSTAIN;
		}




		if (!isset($route_params['guest_type']))
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
