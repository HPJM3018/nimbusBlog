```markdown
# ☁️ Nimbus Blog

Nimbus Blog is a cloud-native blog platform I built to demonstrate cloud engineering skills using AWS services, automation, and deployment best practices. The frontend is built with HTML, CSS, and JavaScript and will be hosted on S3 with CloudFront as CDN. The backend is a REST API built with Laravel 13, running in Docker containers on ECS Fargate, with articles stored in DynamoDB and admin authentication handled by AWS Cognito. The entire infrastructure is provisioned with Terraform as Infrastructure as Code, and deployment is automated using GitHub Actions for CI/CD.

## Current Status

The project is fully functional locally. The backend API supports full CRUD operations on articles (create, read, update, delete). The frontend communicates with the API and includes public pages (home, articles list, article detail) and an admin section (dashboard, create/edit articles). Authentication is implemented with AWS Cognito using real user credentials, with routes protected by a CognitoAuth middleware. DynamoDB is configured and connected, with article data stored in the cloud. CORS is configured to allow communication between the frontend (port 3000) and backend (port 8000) during development.

## Tech Stack

- **Backend**: Laravel 13, PHP 8.3, AWS SDK (DynamoDB, Cognito)
- **Frontend**: HTML5, CSS3, JavaScript (ES6 modules)
- **Database**: Amazon DynamoDB (NoSQL)
- **Authentication**: AWS Cognito (User Pool with ADMIN_USER_PASSWORD_AUTH)
- **Containerization**: Docker
- **Infrastructure**: Terraform (planned)
- **CI/CD**: GitHub Actions (planned)
- **Hosting**: S3 + CloudFront for frontend, ECS Fargate for backend API

## Features Already Implemented

- RESTful API with Laravel
-  DynamoDB integration for article storage
- AWS Cognito authentication with JWT tokens
- Protected admin routes (create, update, delete articles)
- Public article listing and detail views
- Admin dashboard with article management
- Responsive frontend design
- CORS configuration for local development
- Logging system for error tracking

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/articles` | List all articles (public) |
| GET | `/api/articles/{id}` | Get single article (public) |
| POST | `/api/login` | Admin login with Cognito |
| POST | `/api/admin/articles` | Create article (admin only) |
| PUT | `/api/admin/articles/{id}` | Update article (admin only) |
| DELETE | `/api/admin/articles/{id}` | Delete article (admin only) |

## Environment Variables Required

Create a `.env` file in the `backend/` directory with:

```env
APP_NAME="Nimbus Blog API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY= (run php artisan key:generate to generate)

AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
DYNAMODB_TABLE_ARTICLES=articles

COGNITO_USER_POOL_ID=your_user_pool_id
COGNITO_APP_CLIENT_ID=your_client_id
COGNITO_APP_CLIENT_SECRET=your_client_secret
COGNITO_REGION=us-east-1
```

## Local Setup Instructions

1. Clone the repository
2. Install backend dependencies: `cd backend && composer install`
3. Generate app key: `php artisan key:generate`
4. Configure your `.env` file with AWS credentials and Cognito settings
5. Create DynamoDB table named `articles` with partition key `id` (String)
6. Create Cognito User Pool with email sign-in and an admin user
7. Start the backend: `php artisan serve --port=8000`
8. Start the frontend: `cd frontend && python3 -m http.server 3000`
9. Access the site at `http://localhost:3000`
10. Login at `http://localhost:3000/login.html` with your Cognito admin credentials

## Deployment (Planned)

- **Frontend**: S3 bucket with static hosting + CloudFront CDN
- **Backend**: Docker container pushed to ECR, running on ECS Fargate behind an Application Load Balancer
- **Database**: DynamoDB (already configured)
- **Auth**: Cognito (already configured)
- **Infrastructure**: Terraform to provision VPC, subnets, security groups, ECS, ALB, S3, CloudFront
- **CI/CD**: GitHub Actions to automatically deploy on push to main branch

## Project Structure


nimbus-blog/
├── backend/           (Laravel API)
│   ├── app/           (Controllers, Middleware, Models) 
│   ├── routes/        (API routes) 
│   └── .env           (Environment configuration)
├── frontend/          (Static website)
│   ├── index.html     (Home page)
│   ├── articles.html  (Articles list)
│   ├── article.html   (Article detail)
│   ├── login.html     (Admin login)
│   ├── admin/         (Dashboard and edit pages)
│   ├── css/           (Styles)
│   └── js/            (PI calls and auth logic)
└── README.md


## Current Credentials (for local testing)

- **Admin Email**: `admin@nimbus.com`
- **Admin Password**: As configured in your Cognito User Pool
- **Frontend URL**: `http://localhost:3000`
-** Front command**: python3 -m http.server 3000
- **Backend API**: `http://localhost:8000/api`


## Acknowledgments

This project demonstrates real-world cloud engineering practices including infrastructure as code, containerization, CI/CD, serverless databases, and managed authentication services, all working together as a fully functional blog platform.

Group: InfracloudUnits