-- Estructura
CREATE TABLE Client (
    ClientId INTEGER PRIMARY KEY,
    Name TEXT NOT NULL,
    LastName TEXT NOT NULL,
    Identification TEXT NOT NULL
);

CREATE TABLE Product (
    ProductId INTEGER PRIMARY KEY,
    Name TEXT NOT NULL,
    Reference TEXT NOT NULL
);

CREATE TABLE Orders (
    OrderId INTEGER PRIMARY KEY,
    ClientId INTEGER NOT NULL,
    ProductId INTEGER NOT NULL,
    Quantity INTEGER NOT NULL,
    Total INTEGER NOT NULL,
    FOREIGN KEY (ClientId) REFERENCES Client(ClientId),
    FOREIGN KEY (ProductId) REFERENCES Product(ProductId)
);

-- Datos demo (los del enunciado)
INSERT INTO Client (ClientId, Name, LastName, Identification) VALUES
(1,'Pedro','Perez','12345612'),
(2,'Juan','Sanchez','99888773'),
(3,'Maria','Torres','20014032'),
(4,'Marcos','Vargas','85274196'),
(5,'Juanita','Lopez','74165432');

INSERT INTO Product (ProductId, Name, Reference) VALUES
(1,'Televisor','100-342'),
(2,'Nevera','100-343'),
(3,'Microondas','100-344');

INSERT INTO Orders (OrderId, ClientId, ProductId, Quantity, Total) VALUES
(1,1,1,10,15000000),
(2,2,1,2, 3000000),
(3,2,3,3, 2500000),
(4,3,1,6, 9000000),
(5,3,2,5,15000000);
