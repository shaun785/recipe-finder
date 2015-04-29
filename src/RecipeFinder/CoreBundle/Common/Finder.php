<?php


namespace RecipeFinder\CoreBundle\Common;

use RecipeFinder\CoreBundle\Common\Ingredient;
use RecipeFinder\CoreBundle\Common\Recipe;
use RecipeFinder\CoreBundle\Common\Fridge;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
* Finder class to recommend a recipe
* @author Shaunak Deshmukh
* @since 1.0
*/
class Finder {
	
	/*
	  List of Recipes stored as an Array Collection 
	*/
	protected $recipes;
	protected $fridge;
	protected $serializer;
	protected $validator;

	public function __construct($serializer, $fridge, $validator) {
		$this->recipes 		= new ArrayCollection();
		$this->serializer 	= $serializer;
		$this->fridge 		= $fridge;

		$this->validator    = $validator;
	}

	/*
	* Recommends a Recipe 
	* @return Recipe $recipe or null
	*/
	public function recommendRecipe() {
		$potentials = array();

		foreach($this->recipes as $recipe) {
			$ingredients = $recipe->getIngredients();

			if($this->fridge->hasIngredients($ingredients) == true) { //find if all ingredients are in the fridge with valid use by dates
				$potentials[] = $recipe;
				$useByDates   = $this->fridge->getIngredientsUseByDates($ingredients);

				$recipe->setEarliestIngredientUseBy($useByDates[0]); 
			}
		}

		if(count($potentials) > 1) { //return recipe with an earliest use by date for an ingredient
			usort($potentials, function($a, $b) {
				if($a->getEarliestIngredientUseBy() > $b->getEarliestIngredientUseBy()) {
					return 1;
				} else {
					return -1;
				}
			});
		}

		if(count($potentials) == 0) { //if not recipe found, order take out
			$recipe = new Recipe();
			$recipe->setName('Order Takeout');

			$potentials[] = $recipe;
		}

		return $potentials[0];
	}	

	/*
	* Load Recipes
	* @param Json $recipes
	*/
	public function loadRecipes($recipes) {
		//use jms serializer
		$this->recipes = $this->serializer->deserialize($recipes, 'ArrayCollection<RecipeFinder\CoreBundle\Common\Recipe>', 'json');

		foreach($this->recipes as $recipe) { //validate recipes objects
			$errors = $this->validator->validate($recipe);

			if(count($errors) > 0) {
				throw new \Exception('Invalid recipes file supplied!');
			}			
		}
	}

	/*
	* Load Fridge Items
	* @param String $fridgeItems - in CSV Format
	*/
	public function loadFridgeIngredients($fridgeItems) {
		$data = array_map("str_getcsv", preg_split('/\r*\n+|\r+/', $fridgeItems));
		
		foreach($data as $k => $value) {
			$name 		= $value[0];
			$amount  	= is_numeric($value[1]) ? (int)$value[1] : null;
			$unit    	= $value[2];
			$date       = str_replace('/', '-', $value[3]);

			if(\DateTime::createFromFormat('d-m-Y', $date) === FALSE)	//verify if date provided is in valid format
				$date = null;
			else
				$date = new \DateTime($date);


			$item 		= new Ingredient($name, $amount, $unit, $date);

			$this->fridge->addIngredient($item);
		}	

		$errors = $this->validator->validate($this->fridge);

		if(count($errors) > 0) {
			throw new \Exception('Invalid fridge list supplied');
		}		
	}
}