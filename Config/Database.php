<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/connection.php';
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'],
                $config['database']
            );
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            try {
                self::initializeSchema(self::$connection);
            } catch (Throwable $exception) {
                self::$connection = null;
                throw $exception;
            }
        }

        return self::$connection;
    }

    private static function initializeSchema(PDO $connection): void
    {
        // Probe first: normal requests do not need CREATE privileges or execute DDL.
        $tables = [
            'password_reset_tokens' => 'CREATE TABLE IF NOT EXISTS password_reset_tokens (user_id INT UNSIGNED PRIMARY KEY, token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'password_reset_limits' => 'CREATE TABLE IF NOT EXISTS password_reset_limits (bucket CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL DEFAULT 1, window_start DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'app_migrations' => 'CREATE TABLE IF NOT EXISTS app_migrations (name VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'admin_users' => 'CREATE TABLE IF NOT EXISTS admin_users (id INT UNSIGNED PRIMARY KEY, email VARCHAR(254) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'projects' => 'CREATE TABLE IF NOT EXISTS projects (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(200) NOT NULL UNIQUE, payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'agents' => 'CREATE TABLE IF NOT EXISTS agents (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(180) NOT NULL UNIQUE, payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'properties' => 'CREATE TABLE IF NOT EXISTS properties (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(200) NOT NULL UNIQUE, payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'blog_posts' => 'CREATE TABLE IF NOT EXISTS blog_posts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(180) NOT NULL UNIQUE, payload LONGTEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'company_profile' => 'CREATE TABLE IF NOT EXISTS company_profile (id TINYINT UNSIGNED PRIMARY KEY, payload LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'catalog_entries' => 'CREATE TABLE IF NOT EXISTS catalog_entries (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(20) NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE KEY catalog_name (kind,name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'bookings' => "CREATE TABLE IF NOT EXISTS bookings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(32) NOT NULL UNIQUE, request_key CHAR(64) NOT NULL UNIQUE, request_owner CHAR(64) NOT NULL, user_id INT UNSIGNED NULL, kind VARCHAR(10) NOT NULL, listing_slug VARCHAR(200) NOT NULL, listing_title VARCHAR(200) NOT NULL, listing_city VARCHAR(100) NOT NULL DEFAULT '', listing_image VARCHAR(255) NOT NULL DEFAULT '', listing_price VARCHAR(100) NOT NULL DEFAULT '', name VARCHAR(100) NOT NULL, email VARCHAR(254) NOT NULL, phone VARCHAR(40) NOT NULL, message TEXT NOT NULL, request_type VARCHAR(20) NOT NULL, preferred_date DATE NOT NULL, preferred_time TIME NULL, timezone VARCHAR(40) NOT NULL DEFAULT 'Asia/Karachi', status VARCHAR(20) NOT NULL DEFAULT 'pending', admin_note TEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, KEY booking_user (user_id,kind,created_at), KEY booking_visibility (kind,status,created_at), KEY booking_schedule (preferred_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'booking_limits' => 'CREATE TABLE IF NOT EXISTS booking_limits (bucket CHAR(64) PRIMARY KEY, window_start DATETIME NOT NULL, attempts INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'blog_comments' => "CREATE TABLE IF NOT EXISTS blog_comments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, post_id BIGINT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL, visitor_id CHAR(36) NULL, name VARCHAR(100) NOT NULL, email VARCHAR(254) NOT NULL, body TEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, KEY comment_post (post_id,status,created_at), KEY comment_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'site_visitors' => 'CREATE TABLE IF NOT EXISTS site_visitors (id CHAR(36) PRIMARY KEY, token_hash CHAR(64) NOT NULL, linked_user_id INT UNSIGNED NULL, first_seen DATETIME NOT NULL, last_seen DATETIME NOT NULL, KEY visitor_account (linked_user_id), KEY visitor_seen (last_seen)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'site_page_views' => 'CREATE TABLE IF NOT EXISTS site_page_views (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, visitor_id CHAR(36) NOT NULL, user_id INT UNSIGNED NULL, page_kind VARCHAR(20) NOT NULL, page_key VARCHAR(220) NOT NULL, page_title VARCHAR(220) NOT NULL, page_url VARCHAR(500) NOT NULL, view_key CHAR(32) NOT NULL UNIQUE, viewed_at DATETIME NOT NULL, KEY view_visitor (visitor_id,viewed_at), KEY view_page (page_kind,page_key), KEY view_user (user_id), KEY view_date (viewed_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'contact_messages' => "CREATE TABLE IF NOT EXISTS contact_messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(254) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', interest VARCHAR(120) NOT NULL DEFAULT '', message TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        foreach ($tables as $table => $sql) {
            try {
                $connection->query('SELECT 1 FROM `' . $table . '` LIMIT 0');
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '42S02') throw $exception;
                $connection->exec($sql);
            }
        }
        $columns = $connection->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_UNIQUE);
        foreach (['name' => "VARCHAR(100) NOT NULL DEFAULT ''", 'role' => "VARCHAR(10) NOT NULL DEFAULT 'admin'", 'is_blocked' => 'TINYINT(1) NOT NULL DEFAULT 0', 'session_version'=>'INT UNSIGNED NOT NULL DEFAULT 0'] as $column => $definition) {
            if (!isset($columns[$column])) {
                try { $connection->exec('ALTER TABLE admin_users ADD COLUMN `' . $column . '` ' . $definition); }
                catch (PDOException $exception) { if (($exception->errorInfo[1] ?? null) !== 1060) throw $exception; }
            }
        }
        if (!str_contains($columns['id']['Extra'] ?? '', 'auto_increment')) {
            $connection->exec('ALTER TABLE admin_users MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
        self::initializeDefaultAdministrator($connection);
        self::initializeDefaultBlogs($connection);
        self::initializeDefaultProperties($connection);
        self::initializeDefaultProjects($connection);
        self::completeDefaultProjectDetails($connection);
        self::initializeDefaultAgents($connection);
        self::initializeDefaultPublicBlogs($connection);
    }

    private static function initializeDefaultAdministrator(PDO $connection): void
    {
        $migration = 'default_royalmark_admin_v1';
        $check = $connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');
        $check->execute([$migration]);
        if ($check->fetchColumn()) return;
        $connection->beginTransaction();
        try {
            $mark = $connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');
            $mark->execute([$migration]);
            if ($mark->rowCount() === 1) {
                $admin = $connection->prepare("INSERT INTO admin_users (email,password_hash,name,role,is_blocked) VALUES (?,?,?,'admin',0) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),name=VALUES(name),role='admin',is_blocked=0");
                $admin->execute([
                    'royalmarkestate@gmail.com',
                    '$2y$10$VIZ3NWEjFOciKaqS2Ns5teCDAcuxQjePtoIexnQQ7WtKlW.YN8M4C',
                    'Royalmark Administrator',
                ]);
                $delete = $connection->prepare('DELETE FROM admin_users WHERE email <> ?');
                $delete->execute(['royalmarkestate@gmail.com']);
            }
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $exception;
        }
    }
    private static function initializeDefaultBlogs(PDO $connection): void
    {
        $migration='default_dashboard_blogs_v1';
        $check=$connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');
        $check->execute([$migration]);
        if($check->fetchColumn())return;
        $connection->beginTransaction();
        try {
            $mark=$connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');
            $mark->execute([$migration]);
            if($mark->rowCount()===1){
                $insert=$connection->prepare('INSERT IGNORE INTO blog_posts (slug,payload) VALUES (?,?)');
                foreach(require __DIR__.'/default-blogs.php' as $index=>$post){
                    [$title,$slug,$category,$excerpt,$content,$image]=$post;
                    $payload=['title'=>$title,'slug'=>$slug,'category'=>$category,'excerpt'=>$excerpt,'content'=>str_replace('\\n',"\n",$content),'image'=>'./assets/properties/'.$image.'.png','alt'=>$title,'author'=>'Royalmark Estate','tags'=>'property, real estate','type'=>'article','youtube'=>'','youtube_id'=>'','status'=>'draft','published_at'=>date('Y-m-d\TH:i'),'featured'=>$index===0?'1':'0','seo_title'=>'','seo_description'=>'','is_sample'=>true];
                    $insert->execute([$slug,json_encode($payload,JSON_THROW_ON_ERROR)]);
                }
            }
            $connection->commit();
        }catch(Throwable $exception){
            if($connection->inTransaction())$connection->rollBack();
            throw $exception;
        }
    }

    private static function initializeDefaultProperties(PDO $connection): void
    {
        $migration='default_complete_properties_v1';
        $check=$connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');$check->execute([$migration]);if($check->fetchColumn())return;
        $items=json_decode(file_get_contents(__DIR__.'/properties.json'),true,512,JSON_THROW_ON_ERROR);
        $connection->beginTransaction();
        try{
            $mark=$connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');$mark->execute([$migration]);
            if($mark->rowCount()===1){
                $insert=$connection->prepare('INSERT IGNORE INTO properties (slug,payload) VALUES (?,?)');
                $catalog=$connection->prepare('INSERT IGNORE INTO catalog_entries (kind,name) VALUES (?,?)');
                foreach($items as $item){$insert->execute([$item['slug'],json_encode($item,JSON_THROW_ON_ERROR)]);$catalog->execute(['property_type',$item['type']]);$catalog->execute(['location',$item['city']]);}
            }
            $connection->commit();
        }catch(Throwable $exception){if($connection->inTransaction())$connection->rollBack();throw $exception;}
    }

    private static function initializeDefaultProjects(PDO $connection): void
    {
        $migration='default_dashboard_projects_v1';
        $check=$connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');$check->execute([$migration]);if($check->fetchColumn())return;
        $records=require __DIR__.'/default-projects.php';
        $connection->beginTransaction();
        try{
            $mark=$connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');$mark->execute([$migration]);
            if($mark->rowCount()===1){
                $insert=$connection->prepare('INSERT IGNORE INTO projects (slug,payload) VALUES (?,?)');
                $catalog=$connection->prepare('INSERT IGNORE INTO catalog_entries (kind,name) VALUES (?,?)');
                foreach($records as $record){$insert->execute([$record['slug'],json_encode($record,JSON_THROW_ON_ERROR)]);$catalog->execute(['project_type',$record['type']]);$catalog->execute(['location',$record['city']]);}
            }
            $connection->commit();
        }catch(Throwable $exception){if($connection->inTransaction())$connection->rollBack();throw $exception;}
    }

    private static function completeDefaultProjectDetails(PDO $connection): void
    {
        $migration='default_complete_project_details_v1';
        $check=$connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');$check->execute([$migration]);if($check->fetchColumn())return;
        $defaults=require __DIR__.'/default-projects.php';
        $connection->beginTransaction();
        try{
            $mark=$connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');$mark->execute([$migration]);
            if($mark->rowCount()===1){
                $read=$connection->prepare('SELECT payload FROM projects WHERE slug=? FOR UPDATE');
                $write=$connection->prepare('UPDATE projects SET payload=? WHERE slug=?');
                foreach($defaults as $default){
                    $read->execute([$default['slug']]);$payload=$read->fetchColumn();if($payload===false)continue;
                    $record=json_decode($payload,true,512,JSON_THROW_ON_ERROR);if(empty($record['is_sample']))continue;
                    $legacy=[
                        'developer_name'=>'Developer to be confirmed',
                        'developer_description'=>'Developer details have not been provided for this sample concept.',
                        'noc_description'=>'NOC details and supporting documents have not been provided. Please confirm the applicable approvals with the relevant authority.',
                        'overview'=>$record['title'].' is an illustrative '.$record['type'].' project concept in '.$record['city'].'. Images and progress are examples. Contact Royalmark Estate to discuss current options and verify project details.',
                    ];
                    $sampleVideos=empty($record['videos'])||($record['videos']??[])===$default['videos'];
                    $sampleNoc=empty($record['noc_status'])||$record['noc_status']==='Not provided';
                    foreach($default as $key=>$value){
                        if($key==='videos_are_inspiration'&&!$sampleVideos)continue;
                        if(in_array($key,['noc_authority','noc_reference','noc_description','noc_documents'],true)&&!$sampleNoc)continue;
                        if(in_array($key,['slug','title','status','development_status','progress','featured','is_sample'],true))continue;
                        $current=$record[$key]??null;
                        $empty=$current===null||$current===''||$current===[];
                        if(in_array($key,['price_from','price_to','area_from','area_to','total_units','total_floors'],true)&&is_numeric($current)&&(float)$current===0.0)$empty=true;
                        if($empty||(isset($legacy[$key])&&$current===$legacy[$key]))$record[$key]=$value;
                    }
                    $write->execute([json_encode($record,JSON_THROW_ON_ERROR),$default['slug']]);
                }
            }
            $connection->commit();
        }catch(Throwable $exception){if($connection->inTransaction())$connection->rollBack();throw $exception;}
    }

    private static function initializeDefaultAgents(PDO $connection): void
    {
        $migration = 'default_dashboard_agents_v1';
        $check = $connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');
        $check->execute([$migration]);
        if ($check->fetchColumn()) return;
        $records = require __DIR__ . '/default-agents.php';
        $connection->beginTransaction();
        try {
            $mark = $connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');
            $mark->execute([$migration]);
            if ($mark->rowCount() === 1) {
                $insert = $connection->prepare('INSERT IGNORE INTO agents (slug,payload) VALUES (?,?)');
                foreach ($records as $record) $insert->execute([$record['slug'], json_encode($record, JSON_THROW_ON_ERROR)]);
            }
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $exception;
        }
    }

    private static function initializeDefaultPublicBlogs(PDO $connection): void
    {
        $migration='default_public_blogs_v1';
        $check=$connection->prepare('SELECT 1 FROM app_migrations WHERE name=?');$check->execute([$migration]);if($check->fetchColumn())return;
        $records=require __DIR__.'/default-public-blogs.php';
        $connection->beginTransaction();
        try{
            $mark=$connection->prepare('INSERT IGNORE INTO app_migrations (name) VALUES (?)');$mark->execute([$migration]);
            if($mark->rowCount()===1){$insert=$connection->prepare('INSERT IGNORE INTO blog_posts (slug,payload) VALUES (?,?)');foreach($records as $record)$insert->execute([$record['slug'],json_encode($record,JSON_THROW_ON_ERROR)]);}
            $connection->commit();
        }catch(Throwable $error){if($connection->inTransaction())$connection->rollBack();throw $error;}
    }
}
