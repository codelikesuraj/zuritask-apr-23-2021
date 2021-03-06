<?php

/***REGISTSER NEW USER***/
function registerUser($firstName, $lastName, $username, $password)
{
	// include database connection file
	global $conn;

	/*	VALIDATE USER INFORMATION	*/
	// check if first name is a valid string, else return an error
	if(!isNameValid($firstName)){return 'First name is invalid';}
	// check if last name is a valid string, else return an error
	if(!isNameValid($lastName)){return 'Last name is invalid';}
	// check if username is a valid string, else return an error
	if(!isNameValid($username)){return 'Username is invalid';}
	// check if password has a valid length, else return an error
	if(!isPasswordValid($password)){return 'Password is invalid';}

	/*	SAVE USER INFORMATION	*/
	//	if username already exists, return error
	if(checkUser($username) === TRUE){
		return 'username already exists';
	}
	//	if username does not exist, save to database
	else{
		$sql = "INSERT INTO accounts (first_name, last_name, username, password) VALUES (:first_name, :last_name, :username, :password)";
		$values = array(
			':first_name'=>$firstName,
			':last_name'=>$lastName,
			':username'=>$username,
			':password'=>hash('md5', $password)	// hash user password
		);
		
		try{
			$result = $conn->prepare($sql);
			$result->execute($values);
			return TRUE;
		}catch(PDOException $error){
			return 'Error saving user information';
		}
	}	
}

/***LOGIN USER***/
function loginUser($username, $password)
{
	/*	VALIDATE USER INFORMATION	*/
	// check if username is a valid string, else return an error
	if(!isNameValid($username)){return 'Username is invalid';}
	// check if password has a valid length, else return an error
	if(!isPasswordValid($password)){return 'Password is invalid';}

	/*	CHECK IF USER INFORMATION HAS A MATCH	*/
	//	if user exists
	if(checkUser($username) === TRUE){
		$user = checkPassword($username, $password);
		//	if passwords match, set SESSION variables
		if($user !== FALSE){
			$_SESSION['user']['account_id'] = $user['account_id'];
			$_SESSION['user']['first_name'] = $user['first_name'];
			$_SESSION['user']['last_name'] = $user['last_name'];
			$_SESSION['user']['username'] = $user['username'];
			return TRUE;
		}
		//	if passwords do not match
		else{
			return 'password is not correct';
		}
	}
	//	if user does not exist
	else{
		return 'username does not exist';
	}
	
}

/***RESET USER PASSWORD***/
function resetPassword($account_id, $username, $oldPassword, $newPassword)
{
	/*	VALIDATE USER INFORMATION */		
	// check if old password has a valid length, else return an error
	if(!isPasswordValid($oldPassword)){return 'Old password is invalid';}
	// check if new password has a valid length, else return an error
	if(!isPasswordValid($newPassword)){return 'New password is invalid';}

	/*	UPDATE USER INFORMATION	*/
	$user = checkPassword($username, $oldPassword);
	// if current user's password is correct
	if($user !== FALSE){
		// update user information in database
		global $conn;
		$sql = "UPDATE accounts SET password = :new_password WHERE account_id = :account_id";
		$values = array(
			':new_password'=>hash('md5', $newPassword),
			':account_id'=>intval($account_id, 10)
		);
		try{
			$result = $conn->prepare($sql);
			$result->execute($values);
			return 'success';
		}
		catch(PDOException $error){
			return('error updating user information');
		}
	}
	//	if current user's password is not correct
	else{
		return 'incorrect old password';
	}
}

// Check if user is logged in
function checkLogin()
{
	// check if session variables is set, else redirect to login page
	if(!isset($_SESSION['user']['first_name']) || !isset($_SESSION['user']['last_name']) || !isset($_SESSION['user']['username']))
	{
		header('Location: login.php');
		exit(0);
	}
}


// Validate name
function isNameValid($name)
{
	// initialize return variable to true
	$validity = TRUE;
	// remove whitespaces
	$name = trim($name);
	// check if name is empty
	if(empty($name))
	{
		$validity = FALSE;
	}
	// ensure name length is greater than one
	if(strlen($name)<2)
	{
		$validity = FALSE;
	}

	// other checks can be performed here

	return $validity;
}

// Validate password
function isPasswordValid($password)
{
	// initialize return variable to true
	$validity = TRUE;
	// remove whitespaces
	$password = trim($password);
	// ensure password length is greater than 7
	if(strlen($password)<8)
	{
		$validity = FALSE;
	}

	// other checks can be performed here

	return $validity;
}

// Check if username exists
function checkUser($name)
{
	global $conn;

	$sql = "SELECT username FROM accounts WHERE username = :username LIMIT 1";
	$values = array(':username'=>$name);
	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		$result = $result->fetch(PDO::FETCH_ASSOC);
		if($result){
			return TRUE;
		}else{
			return FALSE;
		}
	}catch(PDOException $error){
		die('Error confirming user account');
	}
}

// Check if password match
function checkPassword($username, $password)
{
	global $conn;

	$sql = "SELECT * FROM accounts WHERE username = :username AND password = :password LIMIT 1";
	$values = array(
		':username'=>$username,
		':password'=>hash('md5', $password));
	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		$result = $result->fetch(PDO::FETCH_ASSOC);
		if($result && is_array($result)){
			return $result;
		}else{
			return FALSE;
		}
	}catch(PDOException $error){
		die('Error confirming user information');
	}
}

// Display previously filled values in form input
function displayValue($name)
{
	if(isset($_POST[$name]) && !empty($_POST[$name]))
	{
		echo $_POST[$name];
	}
}

/***ADD A NEW COURSE***/
function addCourse($course_name, $account_id)
{	
	/*	VALIDATE COURSE INFORMATION	*/
	// check if course_name is a valid string, else return an error
	if(!isNameValid($course_name)){return 'course name is invalid';}

	/*	SAVE NEW COURSE	*/
	global $conn;

	$sql = "INSERT INTO courses (course_name, account_id) VALUES (:course_name, :account_id)";
	$values = array(
		':course_name'=>strtolower($course_name),
		':account_id'=>intval($account_id, 10)
	);

	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		return TRUE;
	}catch(PDOException $error){
		return 'error saving course';
	}
}

/***VIEW COURSES***/
function getCourses($account_id)
{
	global $conn;
	$sql = "SELECT * FROM courses WHERE account_id = :id";
	$values = array(':id'=>intval($account_id, 10));
	
	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		$result = $result->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}catch(PDOException $error){
		return FALSE;
	}
}

function getSingleCourse($id)
{
	global $conn;
	$sql = "SELECT * FROM courses WHERE course_id = :id LIMIT 1";
	$values = array(':id'=>intval($id, 10));
	
	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		$result = $result->fetch(PDO::FETCH_ASSOC);
		return $result;
	}catch(PDOException $error){
		return FALSE;
	}
}
/***EDIT A COURSE***/
function updateCourse($course_id, $course_name, $account_id)
{
	/*	VALIDATE COURSE INFORMATION	*/
	// check if course_name is a valid string, else return an error
	if(!isNameValid($course_name)){return 'course name is invalid';}

	/*	SAVE NEW COURSE	*/
	global $conn;

	$sql = "UPDATE courses SET course_name = :course_name WHERE course_id = :course_id AND account_id = :account_id";
	$values = array(
		':course_name'=>strtolower($course_name),
		':course_id'=>intval($course_id, 10),
		':account_id'=>intval($account_id, 10)
	);

	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		return TRUE;
	}catch(PDOException $error){
		return 'error updating course information';
	}
}

/***DELETE A COURSE***/
function deleteCourse($course_id, $account_id)
{
	global $conn;

	$sql = "DELETE FROM courses WHERE course_id = :course_id AND account_id = :account_id";
	$values = array(
		':course_id'=>intval($course_id, 10),
		':account_id'=>intval($account_id, 10)
	);
	try{
		$result = $conn->prepare($sql);
		$result->execute($values);
		return TRUE;
	}catch(PDOException $error){
		return 'error deleting course from database';
	}
}
?>