<?php

namespace RecipeFinder\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use RecipeFinder\CoreBundle\Validator\Constraints\IsJSON;

/*
* Form to input data
* @author Shaunak Deshmukh
* @since 1.0
*/

class RecipeFinderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipes', 'textarea', array('constraints' => array(
                                                new NotBlank(array('message' => 'Please enter a valid csv file'))
                                               )))
            ->add('fridgeItems', 'textarea', array('constraints' => array(
                                                       new NotBlank(array('message' => 'Please enter a valid json file')),
                                                       new IsJSON()
                                                   )))
            ->add('send', 'submit', array('label' => 'Get Recommendation'));
    }

    public function getName()
    {
        return 'recipe_finder';
    }
}