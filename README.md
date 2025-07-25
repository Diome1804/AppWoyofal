# Architecture Projet PHP

**Framework PHP léger et moderne basé sur une architecture MVC avec injection de dépendances, validation automatique et gestion des middlewares.**

---

## 🚀 Installation & Démarrage

```bash
# Installation
composer create-project tabbzero/architectureprojet:dev-main mon-projet
cd mon-projet

# Configuration .env
DB_USER=root
DB_PASSWORD=password  
dsn=mysql:host=localhost;dbname=ma_base;charset=utf8mb4
APP_URL=http://localhost:8000

# Migration & Lancement
php migrations/Migration.php
cd public && php -S localhost:8000
```

---

## 🏗️ Architecture & Structure

**📁 Structure du Projet**

| Dossier | Description | Contenu |
|---------|-------------|---------|
| **`app/config/`** | Configuration | middlewares, rules, services.yml |
| **`app/core/`** | Framework core | abstract, Router, Database, Validator |
| **`src/`** | **Votre code application** | |
| ├─ `controller/` | Contrôleurs | Logique métier et endpoints |
| ├─ `entity/` | Entités | Modèles de données |
| ├─ `repository/` | Repositories | Accès aux données |
| └─ `service/` | Services | Logique applicative |
| **`routes/`** | Définition des routes | Mapping URL → Contrôleur |
| **`migrations/`** | Scripts de migration | Création/modification DB |
| **`public/`** | Point d'entrée | index.php, assets publics |

---

## 🛠️ Utilisation Rapide

### **Routage avec Middlewares**
```php
// routes/route.web.php
$routes = [
    'GET:/api/users' => [
        'controller' => UserController::class,
        'method' => 'index'
    ],
    'POST:/api/users' => [
        'controller' => UserController::class, 
        'method' => 'store',
        'middlewares' => ['auth', 'cryptPassword']
    ],
    'GET:/api/users/{id}' => [
        'controller' => UserController::class,
        'method' => 'show'
    ]
];
```

### **Contrôleur**
```php
namespace App\Controller;
use App\Core\Abstract\AbstractController;

class UserController extends AbstractController
{
    public function index()
    {
        $users = []; // Récupérer depuis repository
        
        $this->renderJson([
            'data' => $users,
            'statut' => 'success', 
            'code' => 200
        ]);
    }
    
    public function store()
    {
        $validator = new Validator();
        $rules = [
            'email' => [['required'], ['isMail']],
            'password' => [['required'], ['minLength', 8]]
        ];
        
        if (!$validator->validate($_POST, $rules)) {
            $this->renderJson([
                'errors' => $validator->getErrors()
            ], 400);
            return;
        }
        
        // Logique de création...
    }
}
```

### **Entité**
```php
namespace App\Entity;
use App\Core\Abstract\AbstractEntity;

class User extends AbstractEntity
{
    public function __construct(
        private int $id,
        private string $nom,
        private string $email
    ) {}
    
    public static function toObject(array $data): static
    {
        return new static($data['id'], $data['nom'], $data['email']);
    }
    
    public function toArray(): array
    {
        return ['id' => $this->id, 'nom' => $this->nom, 'email' => $this->email];
    }
}
```

### **Repository**
```php
namespace App\Repository;
use App\Core\Abstract\AbstractRepository;

class UserRepository extends AbstractRepository
{
    public function selectAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users");
        $stmt->execute();
        
        return array_map([User::class, 'toObject'], $stmt->fetchAll());
    }
    
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        $row = $stmt->fetch();
        return $row ? User::toObject($row) : null;
    }
}
```

---

## ✨ Fonctionnalités Avancées

**🔐 Validation Automatique**
- `required`, `minLength`, `maxLength`, `isMail`, `isPassword`
- `isSenegalPhone`, `isCNI` (spécifique Sénégal)

**🛡️ Middlewares Intégrés**
- `auth` : Vérification authentification
- `cryptPassword` : Cryptage automatique des mots de passe

**📸 Gestion d'Images**
- Upload local et Cloudinary
- Support multi-upload

**💾 Sessions Simplifiées**
```php
Session::set('user', $userData);
$user = Session::get('user', 'id');
Session::destroy();
```

**⚙️ Injection de Dépendances**
```yaml
# app/config/services.yml
repositories:
  userRepository: App\Repository\UserRepository
services:
  userService: App\Service\UserService
```

**🔄 Migration & Seeding**
- Scripts automatisés de création de tables
- Peuplement de données de test

---

## 🎯 Avantages

✅ **Léger** - Framework minimaliste sans bloatware  
✅ **Moderne** - PHP 8.1+, PSR compatible  
✅ **Sécurisé** - Middlewares, validation, cryptage  
✅ **Flexible** - Architecture modulaire  
✅ **API-First** - Réponses JSON standardisées  
✅ **Prêt à l'emploi** - Migration, seeding, exemples  

---

## 📞 Support & Contribution

**🐛 Issues** : GitHub Issues  
**📖 Documentation** : README complet dans le projet  
**🤝 Contributions** : Pull Requests bienvenues  

                                                                                            !!!**Développé avec ❤️ par TableZero**!!!
