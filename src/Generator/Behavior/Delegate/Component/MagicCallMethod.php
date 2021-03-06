<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Behavior\Delegate\Component;

use Propel\Generator\Behavior\Delegate\DelegateBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Relation;

class MagicCallMethod extends BuildComponent
{
    use RelationTrait;

    public function process()
    {
        /** @var DelegateBehavior $behavior */
        $behavior = $this->getBehavior();

        $plural = false;
        $script = '';
        foreach ($behavior->getDelegates() as $delegate => $type) {
            $delegateEntity = $behavior->getDelegateEntity($delegate);
            if ($type == DelegateBehavior::ONE_TO_ONE) {
                $relations = $delegateEntity->getRelationsReferencingEntity($this->getEntity()->getName());
                /** @var Relation $relation */
                $relation = $relations[0];
                $ARClassName = '\\' . $relation->getEntity()->getFullName();
                $ARFQCN = $relation->getEntity()->getFullName(); //$builder->getNewStubObjectBuilder($entity->getEntity())->getFullyQualifiedClassName();
                $relationName = $this->getRefRelationPhpName($relation);
            } else {
                $relations = $this->getEntity()->getRelationsReferencingEntity($delegate);
                /** @var Relation $relation */
                $relation = $relations[0];
                $ARClassName = '\\' . $delegateEntity->getFullName(); // $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($delegateEntity));
                $ARFQCN = $delegateEntity->getFullName(); //$builder->getNewStubObjectBuilder($delegateEntity)->getFullyQualifiedClassName();
                $relationName = $this->getRelationPhpName($relation);
            }

            $script .= "
//type=$type
if (method_exists('$ARFQCN', \$name) && is_callable(array('$ARFQCN', \$name))) {
    if (!\$delegate = \$this->get$relationName()) {
        \$delegate = new $ARClassName();
        \$this->set$relationName(\$delegate);
    }

    return call_user_func_array(array(\$delegate, \$name), \$params);
}";
        }

        $script .= "
throw new \\Propel\\Runtime\\Exception\\BadMethodCallException(sprintf('Call to undefined method: %s.', \$name));
";

        $this->addMethod('__call')
            ->addSimpleParameter('name')
            ->addSimpleParameter('params')
            ->setBody($script);
    }
}
