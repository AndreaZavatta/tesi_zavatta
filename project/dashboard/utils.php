<?php

function getProfileNameByUserId($user_id ,$connection) {
    // Ensure the user_id is sanitized
    $user_id = intval($user_id); 

    // Query to get the profile name for the given user_id
    $query = "
        SELECT p.role_name 
        FROM users u
        INNER JOIN profile p ON u.profile_id = p.id
        WHERE u.id = ?
    ";

    // Prepare the statement
    if ($stmt = $connection->prepare($query)) {
        // Bind parameters
        $stmt->bind_param("i", $user_id);

        // Execute the query
        $stmt->execute();

        // Bind the result
        $stmt->bind_result($role_name);

        // Fetch the result
        if ($stmt->fetch()) {
            // Close the statement
            $stmt->close();

            // Return the profile name
            return $role_name;
        } else {
            // No user or profile found
            $stmt->close();
            return null;
        }
    } else {
        // Query preparation failed
        throw new Exception("Database query failed: " . $connection->error);
    }
}

function getUserPermissions($user_id, $connection) {
    // Ensure the user_id is sanitized
    $user_id = intval($user_id);

    // Query to fetch all permission names for the given user
    $query = "
        SELECT p.permission_name 
        FROM users u
        INNER JOIN profile_permissions pp ON u.profile_id = pp.profile_id
        INNER JOIN permissions p ON pp.permission_id = p.id
        WHERE u.id = ?
    ";

    // Prepare the statement
    if ($stmt = $connection->prepare($query)) {
        // Bind the user ID to the prepared statement
        $stmt->bind_param("i", $user_id);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->get_result();

        // Collect the permission names
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['permission_name'];
        }

        // Close the statement
        $stmt->close();

        // Return the list of permission names
        return $permissions;
    } else {
        // Query preparation failed
        throw new Exception("Database query failed: " . $connection->error);
    }
}

?>
