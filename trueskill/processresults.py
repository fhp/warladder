from trueskill import Rating, TrueSkill, quality, quality_1vs1, rate, rate_1vs1, setup
import json
import sys

jsonString = sys.stdin.read()
try:
	jsonInput = json.loads(jsonString)
except ValueError:
	sys.exit(1)

if "players" not in jsonInput:
	sys.exit(1)
if "matches" not in jsonInput:
	sys.exit(1)

env = TrueSkill(mu=1000.0, sigma=1000.0/3, beta=1000.0/6, tau=1000.0/300, draw_probability=0.1)

players = {}
for player in jsonInput["players"]:
	if "id" not in player or "mu" not in player or "sigma" not in player:
		sys.exit(1)
	sigma = player["sigma"]
	if sigma < 0.001:
		sigma = 0.001
	players[player["id"]] = env.create_rating(player["mu"], sigma)

for match in jsonInput["matches"]:
	teams = []
	if "teams" not in match or "rankings" not in match:
		sys.exit(1)
	teams = []
	ranks = []
	for i in range(len(match["teams"])):
		team = {}
		for player in match["teams"][i]:
			team[player] = players[player]
		teams.append(team)
		ranks.append(match["rankings"][i])
	result = env.rate(teams, ranks)
	for team in result:
		for player in team:
			players[player] = team[player]

playerList = []
for player in players:
	playerList.append({'id': player, 'mu': players[player].mu, 'sigma': players[player].sigma, 'rating': env.expose(players[player])})

def rating(player):
	return player["rating"]

playerRank = sorted(playerList, key=rating, reverse=True)
print json.dumps(playerRank)
