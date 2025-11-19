<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Tracker API - Technical Assessment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .badge {
            display: inline-block;
            background: #48bb78;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .endpoints {
            display: grid;
            gap: 15px;
        }
        
        .endpoint {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .endpoint .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 5px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 0.85em;
        }
        
        .method.get { background: #48bb78; color: white; }
        .method.post { background: #4299e1; color: white; }
        .method.delete { background: #f56565; color: white; }
        
        .endpoint .path {
            color: #2d3748;
            font-family: monospace;
            font-weight: 500;
        }
        
        .endpoint .description {
            color: #718096;
            margin-top: 8px;
            font-size: 0.9em;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature {
            text-align: center;
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
        }
        
        .feature-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .feature h3 {
            color: #2d3748;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        
        .feature p {
            color: #718096;
            font-size: 0.9em;
        }
        
        .links {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        
        .btn-github {
            background: #2d3748;
            color: white;
        }
        
        .tech-stack {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .tech-badge {
            background: #edf2f7;
            color: #2d3748;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Drug Tracker API</h1>
            <p>Technical Assessment - Laravel REST API</p>
            <span class="badge">Live & Running</span>
        </div>

        <div class="section">
            <h2>Overview</h2>
            <p>A production-ready REST API for drug information search and personal medication management. Integrates with the National Library of Medicine's RxNorm database.</p>
        </div>

        <div class="section">
            <h2>Key Features</h2>
            <div class="features">
                <div class="feature">
                    <div class="feature-icon"></div>
                    <h3>Authentication</h3>
                    <p>Secure JWT-based authentication with Laravel Sanctum</p>
                </div>
                <div class="feature">
                    <div class="feature-icon"></div>
                    <h3>Drug Search</h3>
                    <p>Public search endpoint with RxNorm integration</p>
                </div>
                <div class="feature">
                    <div class="feature-icon"></div>
                    <h3>Medication Management</h3>
                    <p>Personal medication list with CRUD operations</p>
                </div>
                <div class="feature">
                    <div class="feature-icon"></div>
                    <h3>Performance</h3>
                    <p>Response caching & rate limiting</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>📡 API Endpoints</h2>
            <div class="endpoints">
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <span class="path">/api/register</span>
                    <div class="description">Register a new user account</div>
                </div>
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <span class="path">/api/login</span>
                    <div class="description">Authenticate and receive access token</div>
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <span class="path">/api/drugs/search?drug_name={name}</span>
                    <div class="description">Search drugs from RxNorm database (Public)</div>
                </div>
                <div class="endpoint">
                    <span class="method post">POST</span>
                    <span class="path">/api/medications</span>
                    <div class="description">Add medication to user's list (Protected)</div>
                </div>
                <div class="endpoint">
                    <span class="method get">GET</span>
                    <span class="path">/api/medications</span>
                    <div class="description">Get all user medications (Protected)</div>
                </div>
                <div class="endpoint">
                    <span class="method delete">DELETE</span>
                    <span class="path">/api/medications/{rxcui}</span>
                    <div class="description">Remove medication from list (Protected)</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>🛠 Tech Stack</h2>
            <div class="tech-stack">
                <span class="tech-badge">Laravel 12</span>
                <span class="tech-badge">PHP 8.2</span>
                <span class="tech-badge">MySQL</span>
                <span class="tech-badge">Laravel Sanctum</span>
                <span class="tech-badge">Guzzle HTTP</span>
                <span class="tech-badge">Docker</span>
                <span class="tech-badge">Railway.app</span>
            </div>
        </div>

        <div class="links">
            <a href="https://github.com/syedarsalan9/drug-tracker/tree/4c283449fbdcc4e94a8a67bc2dc5d58987b4fd13" class="btn btn-github" target="_blank">GitHub Repo</a>
            <a href="https://documenter.getpostman.com/view/18272746/2sB3WyJvqQ" class="btn btn-secondary"> Postman Collection</a>
        </div>

        <div class="footer">
            <p><strong>Developed by:</strong> Syed Arslan Ahmed</p>
            <p>📧 syedarslanahmed99@gmail.com</p>
            <p style="margin-top: 10px;">Built for Housecall Technical Assessment</p>
        </div>
    </div>
</body>
</html>