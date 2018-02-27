<?php

namespace App\GraphQL\Resolver;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class UserResolver implements ResolverInterface, AliasedInterface
{
    public function userById($id)
    {
        return [];
    }

    public static function getAliases()
    {
        return ['userById' => 'userById'];
    }
}