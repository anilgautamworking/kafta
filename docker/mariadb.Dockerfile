FROM mariadb:10.11

# Copy initialization scripts
COPY sql/*.sql /docker-entrypoint-initdb.d/

EXPOSE 3306

CMD ["mariadbd"]

