<?php
// Database configuration for InfinityFree MySQL
$db_host = 'sql305.infinityfree.com'; // Replace with your actual InfinityFree MySQL host
$db_name = 'if0_38739024_db_main'; // Replace with your actual database name
$db_user = 'if0_38739024'; // Replace with your actual username
$db_pass = '7CiuYSLurrAX'; // Replace with your actual password

// Set headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connect to database
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get request action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'get_colleges':
        getColleges($conn);
        break;
    case 'get_courses':
        getCourses($conn);
        break;
    case 'get_subjects':
        getSubjects($conn);
        break;
    case 'get_papers':
        getPapers($conn);
        break;
    case 'add_college':
        addCollege($conn);
        break;
    case 'add_course':
        addCourse($conn);
        break;
    case 'add_subject':
        addSubject($conn);
        break;
    case 'upload_paper':
        uploadPaper($conn);
        break;
    case 'delete_college':
        deleteCollege($conn);
        break;
    case 'delete_course':
        deleteCourse($conn);
        break;
    case 'delete_subject':
        deleteSubject($conn);
        break;
    case 'delete_paper':
        deletePaper($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

// Function to get all colleges
function getColleges($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM colleges");
        $stmt->execute();
        $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $colleges]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to get courses by college ID
function getCourses($conn) {
    $collegeId = isset($_GET['college_id']) ? $_GET['college_id'] : null;
    
    try {
        if ($collegeId) {
            $stmt = $conn->prepare("SELECT * FROM courses WHERE college_id = ?");
            $stmt->execute([$collegeId]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM courses");
            $stmt->execute();
        }
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $courses]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to get subjects by course ID
function getSubjects($conn) {
    $courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;
    
    try {
        if ($courseId) {
            $stmt = $conn->prepare("SELECT * FROM subjects WHERE course_id = ?");
            $stmt->execute([$courseId]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM subjects");
            $stmt->execute();
        }
        
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $subjects]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to get papers with filters
function getPapers($conn) {
    $collegeId = isset($_GET['college_id']) ? $_GET['college_id'] : null;
    $courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;
    $subjectId = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;
    $year = isset($_GET['year']) ? $_GET['year'] : null;
    $semester = isset($_GET['semester']) ? $_GET['semester'] : null;
    
    try {
        $query = "SELECT p.*, c.name as college_name, co.name as course_name, s.name as subject_name 
                 FROM papers p 
                 JOIN colleges c ON p.college_id = c.id 
                 JOIN courses co ON p.course_id = co.id 
                 JOIN subjects s ON p.subject_id = s.id 
                 WHERE 1=1";
        $params = [];
        
        if ($collegeId) {
            $query .= " AND p.college_id = ?";
            $params[] = $collegeId;
        }
        
        if ($courseId) {
            $query .= " AND p.course_id = ?";
            $params[] = $courseId;
        }
        
        if ($subjectId) {
            $query .= " AND p.subject_id = ?";
            $params[] = $subjectId;
        }
        
        if ($year) {
            $query .= " AND p.year = ?";
            $params[] = $year;
        }
        
        if ($semester) {
            $query .= " AND p.semester = ?";
            $params[] = $semester;
        }
        
        $query .= " ORDER BY p.id DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format papers to match frontend expectations
        $formattedPapers = [];
        foreach ($papers as $paper) {
            $formattedPapers[] = [
                'id' => $paper['id'],
                'title' => $paper['title'],
                'college' => $paper['college_name'],
                'course' => $paper['course_name'],
                'subject' => $paper['subject_name'],
                'year' => $paper['year'],
                'semester' => $paper['semester'],
                'file' => $paper['file_link'],
                'date' => $paper['upload_date'],
                'collegeId' => $paper['college_id'],
                'courseId' => $paper['course_id'],
                'subjectId' => $paper['subject_id']
            ];
        }
        
        echo json_encode(['status' => 'success', 'data' => $formattedPapers]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to add a new college
function addCollege($conn) {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $name = isset($data['name']) ? $data['name'] : null;
    
    if (!$name) {
        echo json_encode(['status' => 'error', 'message' => 'College name is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO colleges (name) VALUES (?)");
        $stmt->execute([$name]);
        $id = $conn->lastInsertId();
        
        echo json_encode(['status' => 'success', 'message' => 'College added successfully', 'id' => $id]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to add a new course
function addCourse($conn) {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $name = isset($data['name']) ? $data['name'] : null;
    $collegeId = isset($data['college_id']) ? $data['college_id'] : null;
    
    if (!$name || !$collegeId) {
        echo json_encode(['status' => 'error', 'message' => 'Course name and college ID are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO courses (name, college_id) VALUES (?, ?)");
        $stmt->execute([$name, $collegeId]);
        $id = $conn->lastInsertId();
        
        echo json_encode(['status' => 'success', 'message' => 'Course added successfully', 'id' => $id]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to add a new subject
function addSubject($conn) {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $name = isset($data['name']) ? $data['name'] : null;
    $collegeId = isset($data['college_id']) ? $data['college_id'] : null;
    $courseId = isset($data['course_id']) ? $data['course_id'] : null;
    
    if (!$name || !$collegeId || !$courseId) {
        echo json_encode(['status' => 'error', 'message' => 'Subject name, college ID, and course ID are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO subjects (name, college_id, course_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $collegeId, $courseId]);
        $id = $conn->lastInsertId();
        
        echo json_encode(['status' => 'success', 'message' => 'Subject added successfully', 'id' => $id]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to upload a new paper
function uploadPaper($conn) {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $title = isset($data['title']) ? $data['title'] : null;
    $collegeId = isset($data['college_id']) ? $data['college_id'] : null;
    $courseId = isset($data['course_id']) ? $data['course_id'] : null;
    $subjectId = isset($data['subject_id']) ? $data['subject_id'] : null;
    $year = isset($data['year']) ? $data['year'] : null;
    $semester = isset($data['semester']) ? $data['semester'] : null;
    $fileLink = isset($data['file_link']) ? $data['file_link'] : null;
    
    if (!$title || !$collegeId || !$courseId || !$subjectId || !$year || !$semester || !$fileLink) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO papers (title, college_id, course_id, subject_id, year, semester, file_link, upload_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $collegeId, $courseId, $subjectId, $year, $semester, $fileLink]);
        $id = $conn->lastInsertId();
        
        echo json_encode(['status' => 'success', 'message' => 'Paper uploaded successfully', 'id' => $id]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a college
function deleteCollege($conn) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'College ID is required']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete related papers
        $stmt = $conn->prepare("DELETE FROM papers WHERE college_id = ?");
        $stmt->execute([$id]);
        
        // Delete related subjects
        $stmt = $conn->prepare("DELETE FROM subjects WHERE college_id = ?");
        $stmt->execute([$id]);
        
        // Delete related courses
        $stmt = $conn->prepare("DELETE FROM courses WHERE college_id = ?");
        $stmt->execute([$id]);
        
        // Delete college
        $stmt = $conn->prepare("DELETE FROM colleges WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'College deleted successfully']);
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a course
function deleteCourse($conn) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Course ID is required']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete related papers
        $stmt = $conn->prepare("DELETE FROM papers WHERE course_id = ?");
        $stmt->execute([$id]);
        
        // Delete related subjects
        $stmt = $conn->prepare("DELETE FROM subjects WHERE course_id = ?");
        $stmt->execute([$id]);
        
        // Delete course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Course deleted successfully']);
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a subject
function deleteSubject($conn) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Subject ID is required']);
        return;
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Delete related papers
        $stmt = $conn->prepare("DELETE FROM papers WHERE subject_id = ?");
        $stmt->execute([$id]);
        
        // Delete subject
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Subject deleted successfully']);
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Function to delete a paper
function deletePaper($conn) {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Paper ID is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM papers WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Paper deleted successfully']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
