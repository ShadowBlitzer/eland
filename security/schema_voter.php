<?php

namespace security;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use service\schemas;

class schema_voter implements VoterInterface
{
    const ROLE_ALLOWED_ON_DOMAIN = 'ALLOWED_ON_DOMAIN';

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
		$result = self::ACCESS_ABSTAIN;

		$user = $token->getUser();

		if (!($user instanceof UserInterface))
		{
			return $result;
		}

        /* @var $object Request */

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

        return $result;
    }
}
