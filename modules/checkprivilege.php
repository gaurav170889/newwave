<?php 
error_reporting(0);
function CheckPrivilege(){ 
			if(isset($_GET['route']))
			{
				$route=$_GET['route'];
				//echo "goga";
			}
			else
			{
				$route="";
			}
			//$route = $_GET['route']; /////// clean route
			//$route="";
			if(trim($route == "")){ $route = 'dashboard/index'; }
			if($route != ""){
					list($classname, $functionname) = explode("/", $route);
					if(trim($classname) == ""){ $classname = 'home'; }
					if(trim($functionname) == ""){ $functionname = 'index'; }
					//echo $classname.$functionname;
				   if(file_exists(MODULEPATH.$classname."/class.php"))
				   {
							$class = ucwords($classname);
							if(!class_exists($class))
							{
								include(MODULEPATH.$classname."/class.php");
							}

									if(class_exists($class)){
											$myclass = new $class();
												if(method_exists($myclass,$functionname) && is_callable(array($myclass, $functionname))){
													$_SESSION['navurl']=$class;
													$myclass->$functionname();
													
												}			
												else{ 
													echo "Error"; 
													}
										}
									else
									{ 
										echo "Cannot load module"; 
									}
					}
					else
					{ 
						echo "Module not exists"; 
						echo "</br>";
						echo $classname;
					}
				} /////// route
			else{
				echo "404 : Page not found ";
			}
	      ///////////////////////////////////////////////////////////////////   
 
}
?>
